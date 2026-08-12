document.addEventListener("DOMContentLoaded", () => {
    const container = document.querySelector(
        ".wp-block-woocommerce-product-categories.slide ul.wc-block-product-categories-list.wc-block-product-categories-list--has-images.wc-block-product-categories-list--depth-0",
    ) as HTMLElement;

    if (!container) return;

    let moving = false;
    let startX = 0;
    let startScrollLeft = 0;
    let scrollInterval: ReturnType<typeof setTimeout> | null = null;
    const scrollSpeed: number = 2; // Pixels to move per tick
    const intervalTime: number = 20; // Lower numbers = faster, smoother scrolling (ms)
    const delayAfterActivityInMS: number = 5000;
    const lastTarget = container.scrollWidth - container.clientWidth;
    let currentX = 0;
    let isTicking = false; // Performance throttle gate

    const DRAG_SPEED_MULTIPLIER = 2;

    function updateScrollPosition(): void {
        if (!container || !moving) {
            isTicking = false;
            return;
        }

        const walkX = (currentX - startX) * DRAG_SPEED_MULTIPLIER;
        container.scrollLeft = startScrollLeft - walkX;

        // Release the gate flag so the next pointer move can request another frame
        isTicking = false;
    }

    function startAutoScroll(): void {
        if (moving) return;

        container.scrollLeft += scrollSpeed;

        // const
        if (lastTarget !== container.scrollLeft) {
            scrollInterval = setTimeout(startAutoScroll, intervalTime);
        } else {
            container.scrollLeft = 0;
            scrollInterval = setTimeout(startAutoScroll, 2000);
        }
    }

    function stopAutoScroll(): void {
        if (scrollInterval) {
            clearTimeout(scrollInterval);
            scrollInterval = null;
        }
    }

    container.addEventListener("pointerdown", (e: PointerEvent) => {
        // Only drag with primary mouse button click or touch input
        if (e.button !== 0) return;

        stopAutoScroll();
        if (scrollInterval) clearTimeout(scrollInterval);

        moving = true;

        startX = e.clientX;
        currentX = e.clientX;
        startScrollLeft = container.scrollLeft;
    });
    container.addEventListener("pointermove", (e) => {
        stopAutoScroll();

        currentX = e.clientX;

        if (!isTicking) {
            requestAnimationFrame(updateScrollPosition);
            isTicking = true;
        }

        if (!moving) {
            scrollInterval = setTimeout(
                startAutoScroll,
                delayAfterActivityInMS,
            );
        }
    });

    const handlePointerRelease = (event: PointerEvent): void => {
        if (!moving) return;
        moving = false;

        // Cooldown timer sequence before auto-scroller regains system control
        scrollInterval = setTimeout(() => {
            startAutoScroll();
        }, delayAfterActivityInMS);
    };

    container.addEventListener("pointerup", handlePointerRelease);
    container.addEventListener("pointercancel", handlePointerRelease);

    const observerOptions: IntersectionObserverInit = {
        root: null,
        threshold: 0.05,
    };

    const observer = new IntersectionObserver(
        (entries: IntersectionObserverEntry[]) => {
            entries.forEach((entry) => {
                if (entry.isIntersecting) {
                    // Container entered the viewport -> start scrolling
                    startAutoScroll();
                } else {
                    // Container went out of view -> stop interval completely to save battery/CPU
                    stopAutoScroll();
                }
            });
        },
        observerOptions,
    );

    // Start watching your target element
    observer.observe(container);
});
