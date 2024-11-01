// src/utils/localStorageHelpers.d.ts

export function saveLocal(key: string, value: any): void;
export function loadFromLocalStorage<T>(key: string, fallback: T): T;