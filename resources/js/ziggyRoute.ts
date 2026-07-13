import type { Config } from "ziggy-js";

export type ZiggyRouteFn = (name: string, params?: unknown, absolute?: boolean, config?: Config) => string;
