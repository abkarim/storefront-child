export function createHTMLElement(
    tag: string,
    attributes: Record<string, string> = {},
    children?: HTMLElement | string,
): HTMLElement {
    const element = document.createElement(tag);

    for (const [key, val] of Object.entries(attributes)) {
        element.setAttribute(key, val);
    }

    if (children) {
        if (typeof children === "string") {
            element.textContent = children;
        } else {
            element.appendChild(children);
        }
    }

    return element;
}
