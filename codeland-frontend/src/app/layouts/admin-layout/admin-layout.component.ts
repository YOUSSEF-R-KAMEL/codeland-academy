import { Component, inject, signal } from '@angular/core';
import { Router, RouterModule, RouterOutlet } from '@angular/router';
import { TranslatePipe } from '@ngx-translate/core';

import { LanguageService } from '../../core/services/language.service';
import { ThemeService } from '../../core/services/theme.service';
import { AuthService } from '../../core/services/auth.service';

@Component({
  selector: 'app-admin-layout',
  standalone: true,
  imports: [RouterOutlet, TranslatePipe, RouterModule],
  templateUrl: './admin-layout.component.html',
  styleUrl: './admin-layout.component.scss',
})
export class AdminLayoutComponent {
  readonly languageService = inject(LanguageService);
  readonly themeService = inject(ThemeService);

  private readonly authService = inject(AuthService);
  private readonly router = inject(Router);

  readonly isMobileMenuOpen = signal(false);
  readonly isLoggingOut = signal(false);

  toggleMobileMenu(): void {
    this.isMobileMenuOpen.update((isOpen) => !isOpen);
  }

  closeMobileMenu(): void {
    this.isMobileMenuOpen.set(false);
  }

  logout(): void {
    if (this.isLoggingOut()) return;

    this.isLoggingOut.set(true);
    this.closeMobileMenu();

    this.authService.logout().subscribe({
      next: () => {
        this.router.navigate(['/login']);
      },
      error: () => {
        // AuthService clears the local session in finalize().
        this.router.navigate(['/login']);
      },
    });
  }
}
