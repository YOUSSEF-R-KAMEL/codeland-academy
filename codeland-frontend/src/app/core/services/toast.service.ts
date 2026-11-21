  import { Injectable, signal } from '@angular/core';

  export type ToastType = 'success' | 'error' | 'warning' | 'info';

  export interface ToastMessage {
    id: number;
    type: ToastType;
    message: string;
  }

  @Injectable({
    providedIn: 'root',
  })
  export class ToastService {
    private readonly toastState = signal<ToastMessage | null>(null);

    readonly toast = this.toastState.asReadonly();

    private timeoutId: ReturnType<typeof setTimeout> | null = null;

    show(message: string, type: ToastType = 'success'): void {
      if (this.timeoutId) {
        clearTimeout(this.timeoutId);
      }

      this.toastState.set({
        id: Date.now(),
        type,
        message,
      });

      this.timeoutId = setTimeout(() => {
        this.dismiss();
      }, 3000);
    }

    success(message: string): void {
      this.show(message, 'success');
    }

    error(message: string): void {
      this.show(message, 'error');
    }

    warning(message: string): void {
      this.show(message, 'warning');
    }

    info(message: string): void {
      this.show(message, 'info');
    }

    dismiss(): void {
      if (this.timeoutId) {
        clearTimeout(this.timeoutId);
        this.timeoutId = null;
      }

      this.toastState.set(null);
    }
  }
