document.addEventListener("DOMContentLoaded", () => {
    const container = document.querySelector(
        ".wp-block-woocommerce-product-categories ul.wc-block-product-categories-list.wc-block-product-categories-list--has-images.wc-block-product-categories-list--depth-0",
    ) as HTMLElement;

    if (!container) return;

    let scrollInterval: ReturnType<typeof setTimeout> | null = null;
    const scrollSpeed: number = 2; // Pixels to move per tick
    const intervalTime: number = 30; // Lower numbers = faster, smoother scrolling (ms)

    const lastTarget = container.scrollWidth - container.clientWidth;

    function startAutoScroll(): void {
        // if (scrollInterval) return;

        container.scrollLeft += scrollSpeed;

        // const
        if (lastTarget !== container.scrollLeft) {
            scrollInterval = setTimeout(startAutoScroll, intervalTime);
        } else {
            container.scrollLeft = 0;
            scrollInterval = setTimeout(startAutoScroll, 1000);
        }
    }

    function stopAutoScroll(): void {
        if (scrollInterval) {
            clearTimeout(scrollInterval);
            scrollInterval = null;
        }
    }

    // Event listeners for hover controls
    container.addEventListener("mouseenter", stopAutoScroll);
    container.addEventListener("mouseleave", startAutoScroll);

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
