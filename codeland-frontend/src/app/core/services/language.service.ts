import { DOCUMENT } from '@angular/common';
import { Injectable, inject, signal } from '@angular/core';
import { TranslateService } from '@ngx-translate/core';

export type AppLanguage = 'ar' | 'en';

@Injectable({
  providedIn: 'root',
})
export class LanguageService {
  private readonly translate = inject(TranslateService);
  private readonly document = inject(DOCUMENT);

  private readonly storageKey = 'codeland-language';

  private readonly languageSignal = signal<AppLanguage>('ar');

  readonly currentLanguage = this.languageSignal.asReadonly();

  constructor() {
    this.translate.addLangs(['ar', 'en']);

    const savedLanguage = this.getSavedLanguage();
    this.setLanguage(savedLanguage);
  }

  setLanguage(language: AppLanguage): void {
    this.languageSignal.set(language);

    this.translate.use(language);

    this.document.documentElement.lang = language;
    this.document.documentElement.dir = language === 'ar' ? 'rtl' : 'ltr';

    this.saveLanguage(language);
  }

  toggleLanguage(): void {
    this.setLanguage(this.currentLanguage() === 'ar' ? 'en' : 'ar');
  }

  get isArabic(): boolean {
    return this.currentLanguage() === 'ar';
  }

  private getSavedLanguage(): AppLanguage {
    try {
      const savedLanguage = this.document.defaultView?.localStorage.getItem(
        this.storageKey,
      );

      return savedLanguage === 'en' ? 'en' : 'ar';
    } catch {
      return 'ar';
    }
  }

  private saveLanguage(language: AppLanguage): void {
    try {
      this.document.defaultView?.localStorage.setItem(
        this.storageKey,
        language,
      );
    } catch {
      // Continue even if local storage is unavailable.
    }
  }
}
