const runtimeBaseUrl =
    document.querySelector('meta[name="api-base-url"]')?.getAttribute("content") || import.meta.env.VITE_API_URL || "";

export const baseUrl = runtimeBaseUrl.replace(/\/$/, "");

export function withBaseUrl(path = ""): string {
    const normalizedPath = path.startsWith("/") ? path : `/${path}`;

    return `${baseUrl}${normalizedPath}`;
}
