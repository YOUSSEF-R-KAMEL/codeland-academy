import { ComponentFixture, TestBed } from '@angular/core/testing';

import { ClassSessionsComponent } from './class-sessions.component';

describe('ClassSessionsComponent', () => {
  let component: ClassSessionsComponent;
  let fixture: ComponentFixture<ClassSessionsComponent>;

  beforeEach(async () => {
    await TestBed.configureTestingModule({
      imports: [ClassSessionsComponent]
    })
    .compileComponents();

    fixture = TestBed.createComponent(ClassSessionsComponent);
    component = fixture.componentInstance;
    fixture.detectChanges();
  });

  it('should create', () => {
    expect(component).toBeTruthy();
  });
});
