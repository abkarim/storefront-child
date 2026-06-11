export function updateRootVariable(name: string, value: string) {
    document.documentElement.style.setProperty(name, value);
}
