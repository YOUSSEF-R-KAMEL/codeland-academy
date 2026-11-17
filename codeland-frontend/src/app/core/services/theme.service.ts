import { DOCUMENT } from '@angular/common';
import { Injectable, inject, signal } from '@angular/core';

export type AppTheme = 'light' | 'dark';

@Injectable({
  providedIn: 'root',
})
export class ThemeService {
  private readonly document = inject(DOCUMENT);
  private readonly storageKey = 'codeland-theme';

  private readonly themeSignal = signal<AppTheme>('light');

  get currentTheme(): AppTheme {
    return this.themeSignal();
  }

  get isDark(): boolean {
    return this.themeSignal() === 'dark';
  }

  constructor() {
    const savedTheme = this.getSavedTheme();
    this.setTheme(savedTheme);
  }

  setTheme(theme: AppTheme): void {
    this.themeSignal.set(theme);

    const root = this.document.documentElement;

    root.classList.toggle('dark', theme === 'dark');
    root.setAttribute('data-theme', theme);

    try {
      this.document.defaultView?.localStorage.setItem(this.storageKey, theme);
    } catch {
      // Continue if local storage is unavailable.
    }
  }

  toggleTheme(): void {
    this.setTheme(this.isDark ? 'light' : 'dark');
  }

  private getSavedTheme(): AppTheme {
    try {
      return this.document.defaultView?.localStorage.getItem(
        this.storageKey,
      ) === 'dark'
        ? 'dark'
        : 'light';
    } catch {
      return 'light';
    }
  }
}
