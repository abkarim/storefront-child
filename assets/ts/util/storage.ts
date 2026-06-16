export function setStorageItem(key: string, value: string): void {
    localStorage.setItem(key, value);
}

export function getStorageItem(key: string): string | null {
    return localStorage.getItem(key);
}

export function removeStorageItem(key: string): void {
    localStorage.removeItem(key);
}

const compareListKey = "compareList";

export function addCompareProduct(productId: string): void {
    const compareList = getStorageItem(compareListKey);
    let compareArray: string[] = compareList ? JSON.parse(compareList) : [];

    if (!compareArray.includes(productId)) {
        compareArray.push(productId);
        setStorageItem(compareListKey, JSON.stringify(compareArray));
    }
}

export function removeCompareProduct(productId: string): void {
    const compareList = getStorageItem(compareListKey);
    if (compareList) {
        let compareArray: string[] = JSON.parse(compareList);
        compareArray = compareArray.filter((id) => id !== productId);
        setStorageItem(compareListKey, JSON.stringify(compareArray));
    }
}

export function getCompareProducts(): string[] {
    const compareList = getStorageItem(compareListKey);
    return compareList ? JSON.parse(compareList) : [];
}

export function isCompareProductExists(productId: string): boolean {
    const compareList = getStorageItem(compareListKey);
    if (compareList) {
        const compareArray: string[] = JSON.parse(compareList);
        return compareArray.includes(productId);
    }
    return false;
}
