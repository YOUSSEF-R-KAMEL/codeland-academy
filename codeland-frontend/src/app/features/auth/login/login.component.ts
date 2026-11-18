import { Component, inject, signal } from '@angular/core';
import { CommonModule } from '@angular/common';
import { FormBuilder, ReactiveFormsModule, Validators } from '@angular/forms';
import { Router } from '@angular/router';
import { TranslatePipe } from '@ngx-translate/core';

import { LanguageService } from '../../../core/services/language.service';
import { ThemeService } from '../../../core/services/theme.service';
import { AuthService } from '../../../core/services/auth.service';
import { UserRole } from '../../../core/models/auth.model';

@Component({
  selector: 'app-login',
  standalone: true,
  imports: [CommonModule, ReactiveFormsModule, TranslatePipe],
  templateUrl: './login.component.html',
  styleUrl: './login.component.scss',
})
export class LoginComponent {
  private readonly fb = inject(FormBuilder);
  private readonly authService = inject(AuthService);
  private readonly router = inject(Router);

  readonly languageService = inject(LanguageService);
  readonly themeService = inject(ThemeService);

  readonly currentYear = new Date().getFullYear();
  readonly isLoading = signal(false);
  readonly errorMessage = signal('');
  readonly errorMessageIsTranslation = signal(false);
  readonly showPassword = signal(false);

  readonly accountTypes: {
    value: UserRole;
    labelKey: string;
    placeholderKey: string;
  }[] = [
    {
      value: 'admin',
      labelKey: 'auth.account_types.admin',
      placeholderKey: 'auth.placeholders.admin',
    },
    {
      value: 'instructor',
      labelKey: 'auth.account_types.instructor',
      placeholderKey: 'auth.placeholders.instructor',
    },
    {
      value: 'student',
      labelKey: 'auth.account_types.student',
      placeholderKey: 'auth.placeholders.student',
    },
  ];

  readonly loginForm = this.fb.nonNullable.group({
    type: ['admin' as UserRole, Validators.required],
    email: ['', [Validators.required, Validators.email]],
    password: ['', Validators.required],
  });

  toggleLanguage(): void {
    this.languageService.toggleLanguage();
  }

  toggleTheme(): void {
    this.themeService.toggleTheme();
  }

  togglePasswordVisibility(): void {
    this.showPassword.update((value) => !value);
  }

  onAccountTypeChange(): void {
    this.loginForm.controls.email.reset('');
    this.errorMessage.set('');
    this.errorMessageIsTranslation.set(false);
  }

  login(): void {
    if (this.loginForm.invalid || this.isLoading()) {
      this.loginForm.markAllAsTouched();
      return;
    }

    this.isLoading.set(true);
    this.errorMessage.set('');
    this.errorMessageIsTranslation.set(false);

    this.authService.login(this.loginForm.getRawValue()).subscribe({
      next: (response) => {
        const role = response.data.user.roles[0]?.slug;

        this.isLoading.set(false);

        if (role === 'admin') {
          this.router.navigate(['/admin/dashboard']);
          return;
        }

        this.errorMessage.set('auth.errors.no_admin_access');
        this.errorMessageIsTranslation.set(true);

        this.authService.clearSession();
      },

      error: (error) => {
        this.isLoading.set(false);

        const backendMessage = error.error?.message;

        if (backendMessage) {
          this.errorMessage.set(backendMessage);
          this.errorMessageIsTranslation.set(false);
        } else {
          this.errorMessage.set('auth.errors.sign_in_failed');
          this.errorMessageIsTranslation.set(true);
        }
      },
    });
  }
}
