import {
  ChangeDetectionStrategy,
  Component,
  ElementRef,
  HostListener,
  computed,
  forwardRef,
  inject,
  input,
  signal,
} from '@angular/core';
import { ControlValueAccessor, NG_VALUE_ACCESSOR } from '@angular/forms';
import { TranslatePipe } from '@ngx-translate/core';

export interface SelectOption {
  value: string | number;
  label: string;
}

@Component({
  selector: 'app-select',
  standalone: true,
  imports: [TranslatePipe],
  changeDetection: ChangeDetectionStrategy.OnPush,
  providers: [
    {
      provide: NG_VALUE_ACCESSOR,
      useExisting: forwardRef(() => SelectComponent),
      multi: true,
    },
  ],
  template: `
    <div class="relative">
      <button
        type="button"
        [id]="inputId()"
        (click)="toggle()"
        (keydown.escape)="open.set(false)"
        [disabled]="isDisabled()"
        aria-haspopup="listbox"
        [attr.aria-expanded]="open()"
        class="flex w-full items-center justify-between gap-3 rounded-xl border border-gray-300 bg-white px-4 py-3 text-start text-sm text-gray-900 transition focus:border-blue-500 focus:outline-none focus:ring-2 focus:ring-blue-500/20 disabled:cursor-not-allowed disabled:opacity-60 dark:border-gray-600 dark:bg-gray-900 dark:text-white"
      >
        <span class="truncate" [class.text-gray-400]="!selected()">
          @if (selected(); as s) {
            {{ translateLabels() ? (s.label | translate) : s.label }}
          } @else {
            {{ placeholder() }}
          }
        </span>

        <svg
          class="h-4 w-4 shrink-0 text-gray-500 transition-transform duration-200 dark:text-gray-400"
          [class.rotate-180]="open()"
          viewBox="0 0 20 20"
          fill="currentColor"
          aria-hidden="true"
        >
          <path
            fill-rule="evenodd"
            clip-rule="evenodd"
            d="M5.23 7.21a.75.75 0 011.06.02L10 11.17l3.71-3.94a.75.75 0 111.08 1.04l-4.25 4.5a.75.75 0 01-1.08 0l-4.25-4.5a.75.75 0 01.02-1.06z"
          />
        </svg>
      </button>

      @if (open()) {
        <ul
          role="listbox"
          class="absolute z-30 mt-2 max-h-60 w-full overflow-auto rounded-xl border border-gray-200 bg-white p-1 shadow-lg dark:border-gray-700 dark:bg-gray-800"
        >
          @for (o of options(); track o.value) {
            <li
              role="option"
              [attr.aria-selected]="o.value === value()"
              (click)="choose(o)"
              class="flex cursor-pointer items-center justify-between gap-2 rounded-lg px-3 py-2 text-sm text-gray-700 transition-colors hover:bg-gray-100 aria-selected:bg-blue-50 aria-selected:font-semibold aria-selected:text-blue-700 dark:text-gray-200 dark:hover:bg-gray-700 dark:aria-selected:bg-blue-900/30 dark:aria-selected:text-blue-300"
            >
              <span class="truncate">
                {{ translateLabels() ? (o.label | translate) : o.label }}
              </span>

              @if (o.value === value()) {
                <svg
                  class="h-4 w-4 shrink-0"
                  viewBox="0 0 20 20"
                  fill="currentColor"
                  aria-hidden="true"
                >
                  <path
                    fill-rule="evenodd"
                    clip-rule="evenodd"
                    d="M16.7 5.3a1 1 0 010 1.4l-7.5 7.5a1 1 0 01-1.4 0L3.3 9.7a1 1 0 011.4-1.4l3.8 3.8 6.8-6.8a1 1 0 011.4 0z"
                  />
                </svg>
              }
            </li>
          } @empty {
            <li class="px-3 py-2 text-sm text-gray-500 dark:text-gray-400">
              {{ emptyText() }}
            </li>
          }
        </ul>
      }
    </div>
  `,
})
export class SelectComponent implements ControlValueAccessor {
  options = input<SelectOption[]>([]);
  placeholder = input('');
  emptyText = input('—');
  inputId = input('');
  translateLabels = input(false);
  disabled = input(false);

  value = signal<string | number | null>(null);
  open = signal(false);

  private readonly cvaDisabled = signal(false);

  readonly isDisabled = computed(() => this.disabled() || this.cvaDisabled());

  readonly selected = computed(
    () =>
      this.options().find((option) => option.value === this.value()) ?? null,
  );

  private readonly host = inject(ElementRef<HTMLElement>);

  private onChange: (value: string | number | null) => void = () => {};
  private onTouched: () => void = () => {};

  toggle(): void {
    if (!this.isDisabled()) {
      this.open.update((value) => !value);
    }
  }

  choose(option: SelectOption): void {
    if (this.isDisabled()) return;

    this.value.set(option.value);
    this.onChange(option.value);
    this.onTouched();
    this.open.set(false);
  }

  @HostListener('document:click', ['$event'])
  onDocClick(event: MouseEvent): void {
    if (!this.host.nativeElement.contains(event.target as Node)) {
      this.open.set(false);
    }
  }

  writeValue(value: string | number | null): void {
    this.value.set(value ?? null);
  }

  registerOnChange(fn: (value: string | number | null) => void): void {
    this.onChange = fn;
  }

  registerOnTouched(fn: () => void): void {
    this.onTouched = fn;
  }

  setDisabledState(disabled: boolean): void {
    this.cvaDisabled.set(disabled);
  }
}
