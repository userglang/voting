/**
 * Cooperative Voting System - Branch Selection & Member Search
 * @version 2.0 - Optimized
 */

'use strict';

class BranchSelection {
    // Configuration
    static CONFIG = {
        MIN_SEARCH_LENGTH: 3,
        DEBOUNCE_DELAY: 300,
        ANIMATION_DELAY: 50
    };

    constructor() {
        // Cache DOM elements
        this.dom = {
            branchSelect: document.getElementById('branch_id'),
            searchInput: document.getElementById('search_term'),
            searchHint: document.getElementById('search-hint'),
            loading: document.getElementById('loading'),
            searchResults: document.getElementById('search-results'),
            resultsList: document.getElementById('results-list'),
            resultCount: document.getElementById('result-count'),
            noResults: document.getElementById('no-results'),
            searchTermDisplay: document.getElementById('search-term-display'),
            errorMessage: document.getElementById('error-message'),
            errorText: document.getElementById('error-text'),
            verificationForm: document.getElementById('verification-form'),
            selectedBranchId: document.getElementById('selected_branch_id'),
            selectedMemberId: document.getElementById('selected_member_id')
        };

        // State
        this.state = {
            searchTimeout: null,
            currentSearchTerm: '',
            isSearching: false
        };

        this.init();
    }

    /**
     * Initialize component
     */
    init() {
        if (!this.dom.branchSelect || !this.dom.searchInput) {
            console.warn('[BranchSelection] Required elements not found');
            return;
        }

        this.initBranchSelect();
        this.initSearch();

        console.log('[BranchSelection] Initialized');
    }

    /**
     * Initialize branch selection
     */
    initBranchSelect() {
        this.dom.branchSelect.addEventListener('change', () => {
            this.handleBranchChange();
        });
    }

    /**
     * Handle branch selection change
     */
    handleBranchChange() {
        const branchId = this.dom.branchSelect.value;

        if (branchId) {
            // Enable search input
            this.dom.searchInput.disabled = false;
            this.dom.searchInput.placeholder = 'Type your first name, last name, or member code...';
            this.dom.searchHint.textContent = 'Start typing to search. Minimum 3 characters.';
            this.dom.searchInput.focus();

            // Clear previous results
            this.clearResults();
        } else {
            // Disable search input
            this.dom.searchInput.disabled = true;
            this.dom.searchInput.value = '';
            this.dom.searchInput.placeholder = 'Type your first name, last name, or member code...';
            this.dom.searchHint.textContent = 'Please select a branch first. Minimum 3 characters to search.';
            this.clearResults();
        }
    }

    /**
     * Initialize search functionality
     */
    initSearch() {
        this.dom.searchInput.addEventListener('input', (e) => {
            this.handleSearchInput(e.target.value);
        });

        // Handle enter key
        this.dom.searchInput.addEventListener('keypress', (e) => {
            if (e.key === 'Enter') {
                e.preventDefault();
                this.performSearch();
            }
        });
    }

    /**
     * Handle search input with debouncing
     */
    handleSearchInput(value) {
        clearTimeout(this.state.searchTimeout);

        const searchTerm = value.trim();
        this.state.currentSearchTerm = searchTerm;

        // Clear results if search term is too short
        if (searchTerm.length < BranchSelection.CONFIG.MIN_SEARCH_LENGTH) {
            this.clearResults();
            return;
        }

        // Validate branch selection
        if (!this.dom.branchSelect.value) {
            this.showError('Please select a branch first');
            return;
        }

        // Debounced search
        this.state.searchTimeout = setTimeout(() => {
            this.performSearch();
        }, BranchSelection.CONFIG.DEBOUNCE_DELAY);
    }

    /**
     * Perform member search
     */
    async performSearch() {
        const branchId = this.dom.branchSelect.value;
        const searchTerm = this.state.currentSearchTerm;

        if (!branchId || searchTerm.length < BranchSelection.CONFIG.MIN_SEARCH_LENGTH) {
            return;
        }

        // Prevent concurrent searches
        if (this.state.isSearching) {
            return;
        }

        this.state.isSearching = true;
        this.showLoading();
        this.hideError();

        try {
            const response = await fetch('{{ route("voting.search-member") }}', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.content || '',
                    'Accept': 'application/json'
                },
                body: JSON.stringify({
                    branch_id: branchId,
                    search_term: searchTerm
                })
            });

            if (!response.ok) {
                throw new Error(`HTTP error! status: ${response.status}`);
            }

            const data = await response.json();

            if (data.success && data.members && data.members.length > 0) {
                this.displayResults(data.members);
            } else {
                this.showNoResults(searchTerm);
            }
        } catch (error) {
            console.error('[BranchSelection] Search error:', error);
            this.showError('An error occurred while searching. Please try again.');
        } finally {
            this.hideLoading();
            this.state.isSearching = false;
        }
    }

    /**
     * Display search results
     */
    displayResults(members) {
        this.clearResults();

        // Update result count
        this.dom.resultCount.textContent = members.length;

        // Create result items
        const fragment = document.createDocumentFragment();

        members.forEach((member, index) => {
            const resultItem = this.createResultItem(member, index);
            fragment.appendChild(resultItem);
        });

        this.dom.resultsList.appendChild(fragment);
        this.dom.searchResults.classList.remove('hidden');

        // Announce to screen readers
        this.announce(`Found ${members.length} result${members.length !== 1 ? 's' : ''}`);
    }

    /**
     * Create a single result item
     */
    createResultItem(member, index) {
        const div = document.createElement('div');
        div.className = 'result-item p-4 hover:bg-blue-50 cursor-pointer border-b border-gray-200 last:border-b-0 transition-colors';
        div.setAttribute('role', 'option');
        div.setAttribute('tabindex', '0');
        div.style.animationDelay = `${index * BranchSelection.CONFIG.ANIMATION_DELAY}ms`;

        div.innerHTML = `
            <div class="flex justify-between items-center">
                <div class="flex-1 min-w-0">
                    <p class="font-medium text-gray-900 truncate">
                        ${this.escapeHtml(member.last_name)}, ${this.escapeHtml(member.first_name)}
                        ${member.middle_name ? this.escapeHtml(member.middle_name.charAt(0)) + '.' : ''}
                    </p>
                    <p class="text-xs text-gray-500 mt-0.5">
                        Member Code: ${this.escapeHtml(member.code)}
                    </p>
                </div>
                <svg class="h-5 w-5 text-blue-600 flex-shrink-0 ml-3" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/>
                </svg>
            </div>
        `;

        // Click handler
        div.addEventListener('click', () => this.selectMember(member));

        // Keyboard handler
        div.addEventListener('keypress', (e) => {
            if (e.key === 'Enter' || e.key === ' ') {
                e.preventDefault();
                this.selectMember(member);
            }
        });

        return div;
    }

    /**
     * Select a member and proceed to verification
     */
    selectMember(member) {
        this.dom.selectedBranchId.value = this.dom.branchSelect.value;
        this.dom.selectedMemberId.value = member.id;

        // Show loading state
        this.dom.searchInput.disabled = true;
        this.dom.branchSelect.disabled = true;

        // Submit form
        this.dom.verificationForm.submit();
    }

    /**
     * Show no results message
     */
    showNoResults(searchTerm) {
        this.clearResults();
        this.dom.searchTermDisplay.textContent = searchTerm;
        this.dom.noResults.classList.remove('hidden');
        this.announce('No results found');
    }

    /**
     * Show loading indicator
     */
    showLoading() {
        this.dom.loading.classList.remove('hidden');
        this.dom.searchResults.classList.add('hidden');
        this.dom.noResults.classList.add('hidden');
    }

    /**
     * Hide loading indicator
     */
    hideLoading() {
        this.dom.loading.classList.add('hidden');
    }

    /**
     * Show error message
     */
    showError(message) {
        this.dom.errorText.textContent = message;
        this.dom.errorMessage.classList.remove('hidden');

        // Auto-hide after 5 seconds
        setTimeout(() => this.hideError(), 5000);
    }

    /**
     * Hide error message
     */
    hideError() {
        this.dom.errorMessage.classList.add('hidden');
    }

    /**
     * Clear all results
     */
    clearResults() {
        this.dom.resultsList.innerHTML = '';
        this.dom.searchResults.classList.add('hidden');
        this.dom.noResults.classList.add('hidden');
        this.dom.resultCount.textContent = '0';
    }

    /**
     * Announce to screen readers
     */
    announce(message) {
        const announcement = document.createElement('div');
        announcement.setAttribute('role', 'status');
        announcement.setAttribute('aria-live', 'polite');
        announcement.className = 'sr-only';
        announcement.textContent = message;
        document.body.appendChild(announcement);

        setTimeout(() => announcement.remove(), 1000);
    }

    /**
     * Escape HTML to prevent XSS
     */
    escapeHtml(text) {
        const div = document.createElement('div');
        div.textContent = text || '';
        return div.innerHTML;
    }
}

// Initialize when DOM is ready
if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', () => new BranchSelection());
} else {
    new BranchSelection();
}

// Add CSS for sr-only class if not already present
if (!document.querySelector('style[data-sr-only]')) {
    const style = document.createElement('style');
    style.setAttribute('data-sr-only', 'true');
    style.textContent = `
        .sr-only {
            position: absolute;
            width: 1px;
            height: 1px;
            padding: 0;
            margin: -1px;
            overflow: hidden;
            clip: rect(0, 0, 0, 0);
            white-space: nowrap;
            border-width: 0;
        }
    `;
    document.head.appendChild(style);
}
