import { Component, computed, inject } from '@angular/core';
import { ToastService } from '../../services/toast.service';
import { CommonModule } from '@angular/common';
import { TranslatePipe } from '@ngx-translate/core';
import { TranslateService } from '@ngx-translate/core';
@Component({
  selector: 'app-toast',
  imports: [CommonModule, TranslatePipe],
  standalone: true,
  templateUrl: './toast.component.html',
})
export class ToastComponent {
  private readonly toastService = inject(ToastService);

  readonly toast = this.toastService.toast;
  private readonly translate = inject(TranslateService);
  get currentLang(): string {
    return this.translate.currentLang() || 'en';
  }
  readonly styles = computed(() => {
    const type = this.toast()?.type;

    const variants = {
      success: {
        container: 'border-green-200 bg-green-50 text-green-800',
        icon: 'text-green-600',
        symbol: '✓',
      },
      error: {
        container: 'border-red-200 bg-red-50 text-red-800',
        icon: 'text-red-600',
        symbol: '!',
      },
      warning: {
        container: 'border-amber-200 bg-amber-50 text-amber-800',
        icon: 'text-amber-600',
        symbol: '!',
      },
      info: {
        container: 'border-blue-200 bg-blue-50 text-blue-800',
        icon: 'text-blue-600',
        symbol: 'i',
      },
    };

    return variants[type ?? 'success'];
  });

  dismiss(): void {
    this.toastService.dismiss();
  }
}
