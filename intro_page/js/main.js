document.addEventListener('DOMContentLoaded', () => {

    /* ==========================================================================
       1. Header Scroll State
       ========================================================================== */
    const navbar = document.getElementById('navbar');
    window.addEventListener('scroll', () => {
        if (window.scrollY > 40) {
            navbar.style.boxShadow = '0 10px 30px -10px rgba(0, 0, 0, 0.5)';
            navbar.style.background = 'rgba(9, 9, 11, 0.95)';
        } else {
            navbar.style.boxShadow = 'none';
            navbar.style.background = 'rgba(9, 9, 11, 0.8)';
        }
    });

    /* ==========================================================================
       2. Hero Mockup Interactive Tooltips
       ========================================================================== */
    const simulatedPins = document.querySelectorAll('.simulated-pin');
    const simulatedTooltips = document.querySelectorAll('.simulated-tooltip');

    // Automatically trigger the first pin on load to show interaction
    setTimeout(() => {
        const firstPin = simulatedPins[0];
        if (firstPin) {
            const targetId = firstPin.getAttribute('data-tooltip-id');
            const targetTooltip = document.getElementById(targetId);
            if (targetTooltip) targetTooltip.classList.add('active');
        }
    }, 800);

    simulatedPins.forEach(pin => {
        pin.addEventListener('click', (e) => {
            e.stopPropagation();
            const targetId = pin.getAttribute('data-tooltip-id');
            const targetTooltip = document.getElementById(targetId);
            
            // Toggle active status
            const wasActive = targetTooltip.classList.contains('active');
            
            // Hide all tooltips
            simulatedTooltips.forEach(tip => tip.classList.remove('active'));
            
            if (!wasActive) {
                targetTooltip.classList.add('active');
            }
        });
    });

    // Close hero tooltips when clicking outside
    document.addEventListener('click', () => {
        simulatedTooltips.forEach(tip => tip.classList.remove('active'));
    });


    /* ==========================================================================
       3. Interactive Sandbox Section
       ========================================================================== */
    const sandboxHotspots = document.querySelectorAll('.sandbox-hotspot');
    const viewer = document.getElementById('sandbox-viewer');
    const viewerContent = document.getElementById('viewer-content');
    const emptyState = viewer.querySelector('.empty-state');

    // Trigger the first sandbox hotspot on loading
    setTimeout(() => {
        const firstHotspot = sandboxHotspots[1]; // Highlight the center one first
        if (firstHotspot) {
            firstHotspot.click();
        }
    }, 1200);

    sandboxHotspots.forEach(hotspot => {
        hotspot.addEventListener('click', (e) => {
            e.stopPropagation();
            
            // Remove active classes
            sandboxHotspots.forEach(h => h.classList.remove('active'));
            
            // Activate current
            hotspot.classList.add('active');
            
            // Extract content
            const htmlContent = hotspot.getAttribute('data-html');
            
            // Update viewer
            emptyState.style.display = 'none';
            viewerContent.style.display = 'block';
            viewerContent.innerHTML = htmlContent;
            
            // Subtle glow animation trigger on the viewer border
            viewer.style.borderColor = 'rgba(236, 72, 153, 0.6)';
            setTimeout(() => {
                viewer.style.borderColor = 'rgba(63, 63, 70, 0.4)';
            }, 600);
        });
    });


    /* ==========================================================================
       4. Code Copying Utility
       ========================================================================== */
    const btnCopy = document.getElementById('btn-copy');
    const shortcodeText = document.getElementById('shortcode-text');

    btnCopy.addEventListener('click', () => {
        const textToCopy = shortcodeText.textContent;
        
        navigator.clipboard.writeText(textToCopy).then(() => {
            // Add visual copied state
            btnCopy.classList.add('copied');
            
            // Reset state after a short delay
            setTimeout(() => {
                btnCopy.classList.remove('copied');
            }, 2000);
        }).catch(err => {
            console.error('Failed to copy shortcode text: ', err);
        });
    });
});
