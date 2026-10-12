/**
 * Commerce Sale Scheduler - Storefront Countdown Script
 */
(function () {
    'use strict';

    function padZero(num) {
        return num < 10 ? '0' + num : '' + num;
    }

    function initTimers() {
        var timerElements = document.querySelectorAll('.sale-countdown-timer[data-countdown-end]');

        timerElements.forEach(function (timerEl) {
            if (timerEl.dataset.initialized) {
                return;
            }
            timerEl.dataset.initialized = 'true';

            var endTimestamp = parseInt(timerEl.dataset.countdownEnd, 10) * 1000;
            if (isNaN(endTimestamp)) {
                return;
            }

            var daysEl = timerEl.querySelector('[data-unit="days"]');
            var hoursEl = timerEl.querySelector('[data-unit="hours"]');
            var minutesEl = timerEl.querySelector('[data-unit="minutes"]');
            var secondsEl = timerEl.querySelector('[data-unit="seconds"]');

            function update() {
                var now = Date.now();
                var remaining = endTimestamp - now;

                if (remaining <= 0) {
                    clearInterval(intervalId);
                    timerEl.style.display = 'none';

                    // Dispatch event for any storefront listeners
                    var event = new CustomEvent('salescheduler:expired', {
                        detail: { saleId: timerEl.dataset.saleId }
                    });
                    timerEl.dispatchEvent(event);
                    return;
                }

                var seconds = Math.floor((remaining / 1000) % 60);
                var minutes = Math.floor((remaining / (1000 * 60)) % 60);
                var hours = Math.floor((remaining / (1000 * 60 * 60)) % 24);
                var days = Math.floor(remaining / (1000 * 60 * 60 * 24));

                if (daysEl) daysEl.textContent = padZero(days);
                if (hoursEl) hoursEl.textContent = padZero(hours);
                if (minutesEl) minutesEl.textContent = padZero(minutes);
                if (secondsEl) secondsEl.textContent = padZero(seconds);
            }

            update();
            var intervalId = setInterval(update, 1000);
        });
    }

    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', initTimers);
    } else {
        initTimers();
    }
})();
