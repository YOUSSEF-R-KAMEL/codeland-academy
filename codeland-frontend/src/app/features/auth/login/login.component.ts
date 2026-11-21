import { Component, inject, signal } from '@angular/core';
import { CommonModule } from '@angular/common';
import { FormBuilder, ReactiveFormsModule, Validators } from '@angular/forms';
import { Router } from '@angular/router';
import { TranslatePipe } from '@ngx-translate/core';
import { LanguageService } from '../../../core/services/language.service';
import { ThemeService } from '../../../core/services/theme.service';
import { AuthService } from '../../../core/services/auth.service';

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

  readonly loginForm = this.fb.nonNullable.group({
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

        switch (role) {
          case 'admin':
            this.router.navigate(['/admin/dashboard']);
            break;

          case 'instructor':
            this.router.navigate(['/instructor/dashboard']);
            break;

          case 'student':
            this.router.navigate(['/student']);
            break;

          default:
            this.errorMessage.set('auth.errors.no_admin_access');
            this.errorMessageIsTranslation.set(true);
            this.authService.clearSession();
        }
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
