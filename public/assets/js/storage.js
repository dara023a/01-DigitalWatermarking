// Storage utilities for Spectra Watermarking

const SpectraStorage = {
    // Save watermark run data to session storage
    saveRun: function(runData) {
        try {
            sessionStorage.setItem('watermark_run', JSON.stringify(runData));
        } catch (e) {
            console.error('Failed to save run data:', e);
        }
    },

    // Load watermark run data from session storage
    loadRun: function() {
        try {
            const data = sessionStorage.getItem('watermark_run');
            return data ? JSON.parse(data) : null;
        } catch (e) {
            console.error('Failed to load run data:', e);
            return null;
        }
    },

    // Clear watermark run data
    clearRun: function() {
        try {
            sessionStorage.removeItem('watermark_run');
        } catch (e) {
            console.error('Failed to clear run data:', e);
        }
    },

    // Save metrics to session storage
    saveMetrics: function(metrics) {
        try {
            const existing = this.getMetrics();
            existing.push(metrics);
            sessionStorage.setItem('watermark_metrics', JSON.stringify(existing));
        } catch (e) {
            console.error('Failed to save metrics:', e);
        }
    },

    // Get all metrics from session storage
    getMetrics: function() {
        try {
            const data = sessionStorage.getItem('watermark_metrics');
            return data ? JSON.parse(data) : [];
        } catch (e) {
            console.error('Failed to get metrics:', e);
            return [];
        }
    },

    // Clear all metrics
    clearMetrics: function() {
        try {
            sessionStorage.removeItem('watermark_metrics');
        } catch (e) {
            console.error('Failed to clear metrics:', e);
        }
    }
};
