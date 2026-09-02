export function confirmationText(description: string, resourceName?: string) {
    const index = resourceName ? description.indexOf(resourceName) : -1;

    if (index === -1 || !resourceName) {
        return { before: description, name: '', after: '' };
    }

    return {
        before: description.slice(0, index),
        name: resourceName,
        after: description.slice(index + resourceName.length),
    };
}
