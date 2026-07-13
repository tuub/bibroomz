export function stripEmpty(obj: unknown): unknown {
    if (typeof obj !== "object" || obj === null) return obj;
    const out: Record<string, unknown> = {};
    for (const [k, v] of Object.entries(obj)) {
        if (typeof v === "object" && v !== null) {
            const nested = stripEmpty(v) as Record<string, unknown>;
            if (Object.keys(nested).length > 0) out[k] = nested;
        } else if (v !== "") {
            out[k] = v;
        }
    }
    return out;
}
