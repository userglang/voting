/**
 * Cooperative Voting System - Optimized Ballot JavaScript
 * @version 3.1 - With Candidate Profile Modal
 * @author Voting System Team
 */

'use strict';

class VotingSystem {
    // Configuration constants
    static CONFIG = {
        SESSION_TIMEOUT: 30 * 60, // 30 minutes in seconds
        DEBOUNCE_DELAY: 100,
        ANIMATION_DURATION: 300,
        TOAST_DURATION: 3000,
        NOTIFICATION_FADE: 150
    };

    constructor() {
        // Cache DOM elements
        this.dom = {
            form: document.getElementById('voting-form'),
            reviewBtn: document.getElementById('review-btn'),
            reviewModal: document.getElementById('review-modal'),
            reviewContent: document.getElementById('review-content'),
            voteSummary: document.getElementById('vote-summary'),
            totalSelected: document.getElementById('total-selected'),
            totalAvailable: document.getElementById('total-available'),
            progressPercentage: document.getElementById('progress-percentage'),
            sessionTimer: document.getElementById('session-timer')
        };

        // State management
        this.state = {
            isSubmitting: false,
            totalAvailable: parseInt(this.dom.totalAvailable?.textContent || '0'),
            debounceTimer: null,
            sessionInterval: null
        };

        // Initialize only if form exists
        if (this.dom.form) {
            this.init();
        } else {
            console.warn('[VotingSystem] Voting form not found');
        }
    }

    /**
     * Initialize all components
     */
    init() {
        this.initSessionTimer();
        this.initCandidateSelection();
        this.initReviewModal();
        this.initCandidateProfileModal();
        this.initBeforeUnload();
        this.initKeyboardNavigation();
        this.initAnimations();
        this.updateCounters();

        console.log('[VotingSystem] Initialized successfully');
    }

    // ─────────────────────────────────────────────────────────────
    // SESSION TIMER
    // ─────────────────────────────────────────────────────────────

    /**
     * Session timer with countdown
     */
    initSessionTimer() {
        if (!this.dom.sessionTimer) return;

        let timeLeft = VotingSystem.CONFIG.SESSION_TIMEOUT;

        this.state.sessionInterval = setInterval(() => {
            timeLeft--;

            const minutes = Math.floor(timeLeft / 60);
            const seconds = timeLeft % 60;
            this.dom.sessionTimer.textContent = `${minutes}:${seconds.toString().padStart(2, '0')}`;

            if (timeLeft <= 0) {
                this.handleSessionExpired();
            }
        }, 1000);
    }

    /**
     * Handle session expiration
     */
    handleSessionExpired() {
        clearInterval(this.state.sessionInterval);
        this.showNotification('⏱️ Your session has expired. Redirecting...', 'error');

        setTimeout(() => {
            window.location.href = this.getRoute('select-branch');
        }, 2000);
    }

    // ─────────────────────────────────────────────────────────────
    // CANDIDATE SELECTION
    // ─────────────────────────────────────────────────────────────

    /**
     * Initialize candidate card interactions
     */
    initCandidateSelection() {
        // Use event delegation for better performance
        this.dom.form.addEventListener('click', (e) => {
            // Ignore clicks on the "View Full Profile" button — handled separately
            if (e.target.closest('.view-profile-btn')) return;

            const card = e.target.closest('.candidate-card');
            if (card && !e.target.classList.contains('candidate-checkbox')) {
                e.preventDefault();
                this.toggleCandidate(card);
            }
        });

        // Keyboard support
        this.dom.form.addEventListener('keydown', (e) => {
            if (e.target.closest('.view-profile-btn')) return;

            const card = e.target.closest('.candidate-card');
            if (card && (e.key === 'Enter' || e.key === ' ')) {
                e.preventDefault();
                this.toggleCandidate(card);
            }
        });

        // Add ARIA attributes to all cards
        document.querySelectorAll('.candidate-card').forEach(card => {
            card.setAttribute('tabindex', '0');
            card.setAttribute('role', 'checkbox');
            card.setAttribute('aria-checked', 'false');
        });
    }

    /**
     * Toggle candidate selection
     */
    toggleCandidate(card) {
        const checkbox = card.querySelector('.candidate-checkbox');
        if (!checkbox) return;

        const { positionId, maxVotes } = card.dataset;
        const selectedCount = this.getSelectedCount(positionId);
        const candidateName = card.querySelector('h4')?.textContent.trim();

        // Validate selection limit
        if (!checkbox.checked && selectedCount >= parseInt(maxVotes)) {
            this.showNotification(
                `You can only select up to ${maxVotes} candidate(s) for this position.`,
                'warning'
            );
            this.shakeElement(card);
            return;
        }

        // Toggle selection
        checkbox.checked = !checkbox.checked;
        card.setAttribute('aria-checked', checkbox.checked ? 'true' : 'false');
        this.updateCardStyle(card, checkbox.checked);

        // Show feedback
        if (checkbox.checked) {
            this.showNotification(`✓ Selected: ${candidateName}`, 'success', 2000);
        }

        // Update UI (debounced)
        this.debouncedUpdateCounters();
    }

    /**
     * Get selected count for a position
     */
    getSelectedCount(positionId) {
        return document.querySelectorAll(`input[data-position="${positionId}"]:checked`).length;
    }

    /**
     * Debounced counter update
     */
    debouncedUpdateCounters() {
        clearTimeout(this.state.debounceTimer);
        this.state.debounceTimer = setTimeout(
            () => this.updateCounters(),
            VotingSystem.CONFIG.DEBOUNCE_DELAY
        );
    }

    /**
     * Update card visual style
     */
    updateCardStyle(card, isSelected) {
        const indicator = card.querySelector('.selected-indicator');
        const img = card.querySelector('img, .rounded-full');

        const addClasses = isSelected
            ? ['border-blue-600', 'bg-blue-50', 'shadow-lg', 'ring-2', 'ring-blue-200']
            : ['border-gray-200'];

        const removeClasses = isSelected
            ? ['border-gray-200']
            : ['border-blue-600', 'bg-blue-50', 'shadow-lg', 'ring-2', 'ring-blue-200'];

        card.classList.remove(...removeClasses);
        card.classList.add(...addClasses);

        if (indicator) {
            indicator.classList.toggle('hidden', !isSelected);
        }

        if (img) {
            img.classList.toggle('border-blue-600', isSelected);
            img.classList.toggle('ring-2', isSelected);
            img.classList.toggle('ring-blue-300', isSelected);
        }

        if (isSelected) {
            card.classList.add('animate-pulse-once');
            setTimeout(() => card.classList.remove('animate-pulse-once'), 600);
        }
    }

    // ─────────────────────────────────────────────────────────────
    // COUNTERS & PROGRESS
    // ─────────────────────────────────────────────────────────────

    /**
     * Update all counters
     */
    updateCounters() {
        this.updatePositionCounters();
        this.updateOverallProgress();
        this.updateSubmitButton();
    }

    /**
     * Update position-specific counters
     */
    updatePositionCounters() {
        document.querySelectorAll('.position-status').forEach(status => {
            const { position: positionId, max: maxVotes } = status.dataset;
            const count = this.getSelectedCount(positionId);

            const countEl = status.querySelector('.selected-count');
            if (countEl) {
                this.animateNumber(countEl, parseInt(countEl.textContent), count);
            }

            this.updateStatusBadge(status, count, parseInt(maxVotes));
        });
    }

    /**
     * Update status badge styling
     */
    updateStatusBadge(badge, count, max) {
        const baseClasses = 'inline-flex items-center px-3 py-1 rounded-full text-sm font-medium position-status';
        let colorClasses;

        if (count === max) {
            colorClasses = 'bg-green-100 text-green-800 ring-2 ring-green-300';
        } else if (count > 0) {
            colorClasses = 'bg-blue-100 text-blue-800 ring-2 ring-blue-300';
        } else {
            colorClasses = 'bg-gray-100 text-gray-800';
        }

        badge.className = `${baseClasses} ${colorClasses}`;
    }

    /**
     * Animate number changes
     */
    animateNumber(element, from, to) {
        if (from === to) return;

        element.style.transform = 'scale(1.2)';
        element.textContent = to;

        setTimeout(() => {
            element.style.transform = 'scale(1)';
        }, 200);
    }

    /**
     * Update overall progress
     */
    updateOverallProgress() {
        const totalSelected = document.querySelectorAll('.candidate-checkbox:checked').length;

        if (this.dom.totalSelected) {
            this.animateNumber(
                this.dom.totalSelected,
                parseInt(this.dom.totalSelected.textContent),
                totalSelected
            );
        }

        if (this.dom.progressPercentage && this.state.totalAvailable > 0) {
            const percentage = Math.round((totalSelected / this.state.totalAvailable) * 100);
            this.dom.progressPercentage.textContent = `${percentage}%`;
        }

        this.updateVoteSummary(totalSelected);
    }

    /**
     * Update submit button state
     */
    updateSubmitButton() {
        if (!this.dom.reviewBtn) return;

        const totalSelected = document.querySelectorAll('.candidate-checkbox:checked').length;
        const wasDisabled = this.dom.reviewBtn.disabled;
        this.dom.reviewBtn.disabled = totalSelected === 0;

        if (wasDisabled && !this.dom.reviewBtn.disabled) {
            this.dom.reviewBtn.classList.add('animate-pulse-once');
            setTimeout(() => {
                this.dom.reviewBtn.classList.remove('animate-pulse-once');
            }, 1000);
        }
    }

    /**
     * Update vote summary text
     */
    updateVoteSummary(totalSelected) {
        if (!this.dom.voteSummary) return;

        let newText;

        if (totalSelected === 0) {
            newText = 'You must vote for at least one candidate before submitting.';
        } else if (totalSelected === this.state.totalAvailable) {
            newText = `✓ Perfect! You have selected all ${totalSelected} candidates. Ready to submit.`;
        } else {
            const remaining = this.state.totalAvailable - totalSelected;
            newText = `You have selected ${totalSelected} candidate(s). ${remaining} more available.`;
        }

        if (this.dom.voteSummary.textContent !== newText) {
            this.dom.voteSummary.style.opacity = '0';
            setTimeout(() => {
                this.dom.voteSummary.textContent = newText;
                this.dom.voteSummary.style.opacity = '1';
            }, VotingSystem.CONFIG.NOTIFICATION_FADE);
        }
    }

    // ─────────────────────────────────────────────────────────────
    // REVIEW MODAL
    // ─────────────────────────────────────────────────────────────

    /**
     * Initialize review modal
     */
    initReviewModal() {
        if (!this.dom.reviewBtn || !this.dom.reviewModal) return;

        this.dom.reviewBtn.addEventListener('click', () => this.showReviewModal());

        ['close-modal', 'close-modal-btn'].forEach(id => {
            document.getElementById(id)?.addEventListener('click', () => this.hideReviewModal());
        });

        document.getElementById('confirm-submit')?.addEventListener('click', () => this.confirmSubmission());

        this.dom.reviewModal.addEventListener('click', (e) => {
            if (e.target === this.dom.reviewModal) this.hideReviewModal();
        });

        document.addEventListener('keydown', (e) => {
            if (e.key === 'Escape' && !this.dom.reviewModal.classList.contains('hidden')) {
                this.hideReviewModal();
            }
        });
    }

    /**
     * Show review modal
     */
    showReviewModal() {
        const selections = this.getSelections();

        if (selections.length === 0) {
            this.showNotification('Please select at least one candidate before reviewing.', 'warning');
            return;
        }

        this.dom.reviewContent.innerHTML = this.generateReviewHTML(selections);
        this.dom.reviewModal.classList.remove('hidden');
        this.dom.reviewModal.classList.add('animate-fade-in');

        this.trapFocus(this.dom.reviewModal);
        this.announce(`Review modal opened. ${selections.reduce((sum, s) => sum + s.candidates.length, 0)} candidates selected.`);
    }

    /**
     * Hide review modal
     */
    hideReviewModal() {
        this.dom.reviewModal.classList.add('animate-fade-out');

        setTimeout(() => {
            this.dom.reviewModal.classList.add('hidden');
            this.dom.reviewModal.classList.remove('animate-fade-in', 'animate-fade-out');
        }, VotingSystem.CONFIG.ANIMATION_DURATION);
    }

    /**
     * Get current selections
     */
    getSelections() {
        const selections = [];

        document.querySelectorAll('.candidate-checkbox:checked').forEach(checkbox => {
            const card = checkbox.closest('.candidate-card');
            const positionSection = card.closest('.bg-white');
            const positionTitle = positionSection?.querySelector('h3')?.textContent.trim();
            const candidateName = card.querySelector('h4')?.textContent.trim();
            const candidateImage = card.querySelector('img')?.src;

            if (positionTitle && candidateName) {
                let position = selections.find(s => s.position === positionTitle);

                if (!position) {
                    position = { position: positionTitle, candidates: [] };
                    selections.push(position);
                }

                position.candidates.push({ name: candidateName, image: candidateImage });
            }
        });

        return selections;
    }

    /**
     * Generate review HTML
     */
    generateReviewHTML(selections) {
        const totalCandidates = selections.reduce((sum, s) => sum + s.candidates.length, 0);

        let html = `
            <div class="mb-4 p-4 bg-blue-50 border border-blue-200 rounded-lg">
                <p class="text-sm text-blue-900">
                    <strong>Total selections:</strong> ${totalCandidates} candidate(s) across ${selections.length} position(s)
                </p>
            </div>
            <div class="space-y-4">
        `;

        selections.forEach((selection, index) => {
            html += `
                <div class="border border-gray-200 rounded-lg p-4 bg-gray-50" style="animation: slideIn 0.3s ease ${index * 0.1}s both">
                    <h4 class="font-bold text-gray-900 mb-3">${this.escapeHtml(selection.position)}</h4>
                    <div class="space-y-2">
            `;

            selection.candidates.forEach((candidate, idx) => {
                const imageHTML = candidate.image
                    ? `<img src="${candidate.image}" alt="${this.escapeHtml(candidate.name)}" class="w-10 h-10 rounded-full object-cover mr-3 border-2 border-green-500">`
                    : `<div class="w-10 h-10 rounded-full bg-green-500 flex items-center justify-center mr-3 text-white font-bold">${idx + 1}</div>`;

                html += `
                    <div class="flex items-center text-sm text-gray-700 p-2 bg-white rounded hover:bg-gray-100 transition-colors">
                        ${imageHTML}
                        <svg class="w-5 h-5 text-green-500 mr-2 flex-shrink-0" fill="currentColor" viewBox="0 0 20 20">
                            <path fill-rule="evenodd" d="M16.707 5.293a1 1 0 010 1.414l-8 8a1 1 0 01-1.414 0l-4-4a1 1 0 011.414-1.414L8 12.586l7.293-7.293a1 1 0 011.414 0z" clip-rule="evenodd"/>
                        </svg>
                        <span class="flex-1 font-medium">${this.escapeHtml(candidate.name)}</span>
                    </div>
                `;
            });

            html += `</div></div>`;
        });

        html += '</div>';
        return html;
    }

    // ─────────────────────────────────────────────────────────────
    // CANDIDATE PROFILE MODAL
    // ─────────────────────────────────────────────────────────────

    /**
     * Initialize candidate profile modal interactions
     */
    initCandidateProfileModal() {
        const modal = document.getElementById('profile-modal');
        if (!modal) return;

        // Close button handlers
        ['close-profile-modal', 'close-profile-modal-btn'].forEach(id => {
            document.getElementById(id)?.addEventListener('click', () => this.hideCandidateProfileModal());
        });

        // Click outside to close
        modal.addEventListener('click', (e) => {
            if (e.target === modal) this.hideCandidateProfileModal();
        });

        // ESC to close (only when profile modal is open)
        document.addEventListener('keydown', (e) => {
            if (e.key === 'Escape' && !modal.classList.contains('hidden')) {
                e.stopImmediatePropagation(); // prevent review modal from also closing
                this.hideCandidateProfileModal();
            }
        });

        // Delegate "View Full Profile" button clicks via the form
        this.dom.form.addEventListener('click', (e) => {
            const btn = e.target.closest('.view-profile-btn');
            if (!btn) return;

            e.preventDefault();
            e.stopPropagation(); // prevent candidate card toggle

            const infoEl    = btn.closest('[data-candidate-name]');
            const name      = infoEl?.dataset.candidateName    || '';
            const profile   = infoEl?.dataset.candidateProfile || 'No background profile available.';
            const imageUrl  = infoEl?.dataset.candidateImage   || '';

            this.showCandidateProfileModal(name, profile, imageUrl);
        });
    }

    /**
     * Show candidate profile modal
     */
    showCandidateProfileModal(name, profile, imageUrl) {
        const modal  = document.getElementById('profile-modal');
        const avatar = document.getElementById('profile-modal-avatar');
        const title  = document.getElementById('profile-modal-title');
        const body   = document.getElementById('profile-modal-body');

        if (!modal || !avatar || !title || !body) return;

        // Populate content
        title.textContent = name;
        body.textContent  = profile || 'No background profile available.';

        // Build avatar
        if (imageUrl) {
            avatar.innerHTML = `
                <img src="${this.escapeHtml(imageUrl)}"
                     alt="${this.escapeHtml(name)}"
                     class="w-16 h-16 rounded-full object-cover border-2 border-white/50 shadow-md">
            `;
        } else {
            const initials = name
                .split(' ')
                .map(n => n[0])
                .slice(0, 2)
                .join('')
                .toUpperCase();

            avatar.innerHTML = `
                <div class="w-16 h-16 rounded-full bg-white/20 border-2 border-white/50 flex items-center justify-center shadow-md">
                    <span class="text-2xl font-bold text-white">${this.escapeHtml(initials)}</span>
                </div>
            `;
        }

        // Show modal
        modal.classList.remove('hidden');
        modal.classList.add('animate-fade-in');

        // Accessibility
        this.trapFocus(modal);
        this.announce(`Viewing background profile for ${name}`);
    }

    /**
     * Hide candidate profile modal
     */
    hideCandidateProfileModal() {
        const modal = document.getElementById('profile-modal');
        if (!modal) return;

        modal.classList.add('animate-fade-out');

        setTimeout(() => {
            modal.classList.add('hidden');
            modal.classList.remove('animate-fade-in', 'animate-fade-out');
        }, VotingSystem.CONFIG.ANIMATION_DURATION);
    }

    // ─────────────────────────────────────────────────────────────
    // SUBMISSION
    // ─────────────────────────────────────────────────────────────

    /**
     * Confirm and submit votes
     */
    confirmSubmission() {
        const confirmBtn = document.getElementById('confirm-submit');

        if (confirmBtn) {
            confirmBtn.disabled = true;
            confirmBtn.innerHTML = this.getLoadingButtonHTML('Submitting...');
        }

        this.state.isSubmitting = true;
        window.__votingSubmitting = true;
        window.removeEventListener('beforeunload', this.beforeUnloadHandler);

        this.showLoadingOverlay();

        this.dom.form.querySelectorAll('button').forEach(btn => btn.disabled = true);

        setTimeout(() => {
            if (typeof this.dom.form.requestSubmit === 'function') {
                this.dom.form.requestSubmit();
            } else {
                this.dom.form.submit();
            }
        }, 500);
    }

    /**
     * Get loading button HTML
     */
    getLoadingButtonHTML(text = 'Loading...') {
        return `
            <svg class="animate-spin -ml-1 mr-2 h-5 w-5 text-white inline-block" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
                <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
            </svg>
            <span>${text}</span>
        `;
    }

    /**
     * Show loading overlay
     */
    showLoadingOverlay() {
        const overlay = document.createElement('div');
        overlay.id = 'loading-overlay';
        overlay.className = 'fixed inset-0 bg-black bg-opacity-75 flex items-center justify-center z-[70] animate-fade-in';
        overlay.innerHTML = `
            <div class="bg-white rounded-lg p-8 text-center max-w-md mx-4 animate-scale-in">
                <div class="flex justify-center mb-4">
                    <svg class="animate-spin h-16 w-16 text-blue-600" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
                        <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                        <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                    </svg>
                </div>
                <h3 class="text-xl font-bold text-gray-900 mb-2">Submitting Your Vote</h3>
                <p class="text-gray-600">Please wait while we securely record your selections...</p>
            </div>
        `;
        document.body.appendChild(overlay);
    }

    // ─────────────────────────────────────────────────────────────
    // UTILITIES
    // ─────────────────────────────────────────────────────────────

    /**
     * Initialize beforeunload protection
     */
    initBeforeUnload() {
        this.beforeUnloadHandler = (e) => {
            if (document.querySelectorAll('.candidate-checkbox:checked').length > 0 && !this.state.isSubmitting) {
                e.preventDefault();
                e.returnValue = 'You have unsaved votes. Are you sure you want to leave?';
                return e.returnValue;
            }
        };

        window.addEventListener('beforeunload', this.beforeUnloadHandler);

        if (this.dom.form) {
            this.dom.form.addEventListener('submit', () => {
                this.state.isSubmitting = true;
                window.removeEventListener('beforeunload', this.beforeUnloadHandler);
            });
        }
    }

    /**
     * Initialize keyboard navigation
     */
    initKeyboardNavigation() {
        document.addEventListener('keydown', (e) => {
            if ((e.ctrlKey || e.metaKey) && ['Enter', 's'].includes(e.key)) {
                e.preventDefault();
                if (this.dom.reviewBtn && !this.dom.reviewBtn.disabled) {
                    this.showReviewModal();
                }
            }
        });
    }

    /**
     * Initialize scroll animations
     */
    initAnimations() {
        if (!('IntersectionObserver' in window)) return;

        const observer = new IntersectionObserver((entries) => {
            entries.forEach(entry => {
                if (entry.isIntersecting) {
                    entry.target.classList.add('animate-fade-in-up');
                    observer.unobserve(entry.target);
                }
            });
        }, { threshold: 0.1, rootMargin: '50px' });

        document.querySelectorAll('.candidate-card').forEach(card => {
            observer.observe(card);
        });
    }

    /**
     * Shake element animation
     */
    shakeElement(element) {
        element.classList.add('animate-shake');
        setTimeout(() => element.classList.remove('animate-shake'), 600);
    }

    /**
     * Show toast notification
     */
    showNotification(message, type = 'info', duration = VotingSystem.CONFIG.TOAST_DURATION) {
        const toast = document.createElement('div');
        toast.className = `fixed top-4 right-4 z-[80] max-w-sm p-4 rounded-lg shadow-lg animate-slide-in-right ${this.getToastClass(type)}`;
        toast.setAttribute('role', 'alert');
        toast.innerHTML = `
            <div class="flex items-start">
                <div class="flex-1">
                    <p class="text-sm font-medium">${this.escapeHtml(message)}</p>
                </div>
                <button type="button" class="ml-4 flex-shrink-0" onclick="this.parentElement.parentElement.remove()" aria-label="Close">
                    <svg class="w-4 h-4" fill="currentColor" viewBox="0 0 20 20">
                        <path fill-rule="evenodd" d="M4.293 4.293a1 1 0 011.414 0L10 8.586l4.293-4.293a1 1 0 111.414 1.414L11.414 10l4.293 4.293a1 1 0 01-1.414 1.414L10 11.414l-4.293 4.293a1 1 0 01-1.414-1.414L8.586 10 4.293 5.707a1 1 0 010-1.414z" clip-rule="evenodd"/>
                    </svg>
                </button>
            </div>
        `;

        document.body.appendChild(toast);

        if (duration > 0) {
            setTimeout(() => {
                toast.classList.add('animate-slide-out-right');
                setTimeout(() => toast.remove(), VotingSystem.CONFIG.ANIMATION_DURATION);
            }, duration);
        }
    }

    /**
     * Get toast CSS classes by type
     */
    getToastClass(type) {
        const classes = {
            success: 'bg-green-50 border border-green-200 text-green-800',
            error:   'bg-red-50 border border-red-200 text-red-800',
            warning: 'bg-yellow-50 border border-yellow-200 text-yellow-800',
            info:    'bg-blue-50 border border-blue-200 text-blue-800'
        };
        return classes[type] || classes.info;
    }

    /**
     * Trap focus within modal
     */
    trapFocus(modal) {
        const focusable = Array.from(
            modal.querySelectorAll('button, [href], input, select, textarea, [tabindex]:not([tabindex="-1"])')
        );
        const firstElement = focusable[0];
        const lastElement  = focusable[focusable.length - 1];

        const handleKeydown = (e) => {
            if (e.key !== 'Tab') return;

            if (e.shiftKey) {
                if (document.activeElement === firstElement) {
                    lastElement.focus();
                    e.preventDefault();
                }
            } else {
                if (document.activeElement === lastElement) {
                    firstElement.focus();
                    e.preventDefault();
                }
            }
        };

        modal.addEventListener('keydown', handleKeydown);
        firstElement?.focus();
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

    /**
     * Get route URL
     */
    getRoute(routeName) {
        const routes = {
            'select-branch':  '/voting',
            'verification':   '/voting/verify',
            'update-info':    '/voting/update-info',
            'ballot':         '/voting/ballot',
            'confirmation':   '/voting/confirmation'
        };
        return routes[routeName] || '/voting';
    }
}

// ─────────────────────────────────────────────────────────────────
// BOOTSTRAP
// ─────────────────────────────────────────────────────────────────

if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', () => new VotingSystem());
} else {
    new VotingSystem();
}

// ─────────────────────────────────────────────────────────────────
// CSS ANIMATIONS
// ─────────────────────────────────────────────────────────────────

const style = document.createElement('style');
style.textContent = `
    @keyframes shake {
        0%, 100% { transform: translateX(0); }
        10%, 30%, 50%, 70%, 90% { transform: translateX(-10px); }
        20%, 40%, 60%, 80% { transform: translateX(10px); }
    }
    @keyframes pulse-once {
        0%, 100% { transform: scale(1); }
        50% { transform: scale(1.05); }
    }
    @keyframes scale-in {
        from { transform: scale(0.95); opacity: 0; }
        to   { transform: scale(1);    opacity: 1; }
    }
    @keyframes fade-in {
        from { opacity: 0; }
        to   { opacity: 1; }
    }
    @keyframes fade-in-up {
        from { opacity: 0; transform: translateY(20px); }
        to   { opacity: 1; transform: translateY(0);    }
    }
    @keyframes slide-in-right {
        from { opacity: 0; transform: translateX(100px); }
        to   { opacity: 1; transform: translateX(0);     }
    }
    @keyframes slide-out-right {
        from { opacity: 1; transform: translateX(0);     }
        to   { opacity: 0; transform: translateX(100px); }
    }
    @keyframes slideIn {
        from { opacity: 0; transform: translateX(-20px); }
        to   { opacity: 1; transform: translateX(0);     }
    }

    .animate-shake          { animation: shake 0.6s; }
    .animate-pulse-once     { animation: pulse-once 0.6s; }
    .animate-scale-in       { animation: scale-in 0.3s; }
    .animate-fade-in        { animation: fade-in 0.3s; }
    .animate-fade-out       { animation: fade-in 0.3s reverse; }
    .animate-fade-in-up     { animation: fade-in-up 0.5s; }
    .animate-slide-in-right { animation: slide-in-right 0.4s; }
    .animate-slide-out-right{ animation: slide-out-right 0.3s; }

    /* Profile modal body smooth scale-in */
    #profile-modal > div   { animation: scale-in 0.25s cubic-bezier(0.34, 1.56, 0.64, 1); }

    /* Smooth transitions */
    .candidate-card, .position-status, button { transition: all 0.2s ease; }
    #vote-summary { transition: opacity 0.15s ease; }

    .sr-only {
        position: absolute;
        width: 1px; height: 1px;
        padding: 0; margin: -1px;
        overflow: hidden;
        clip: rect(0,0,0,0);
        white-space: nowrap;
        border-width: 0;
    }
`;
document.head.appendChild(style);
