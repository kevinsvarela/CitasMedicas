import { ComponentFixture, TestBed } from '@angular/core/testing';
import { FrmMedico } from './frm-medico';

describe('FrmMedico', () => {
  let component: FrmMedico;
  let fixture: ComponentFixture<FrmMedico>;

  beforeEach(async () => {
    await TestBed.configureTestingModule({
      imports: [FrmMedico],
    }).compileComponents();

    fixture = TestBed.createComponent(FrmMedico);
    component = fixture.componentInstance;
    await fixture.whenStable();
  });

  it('should create', () => {
    expect(component).toBeTruthy();
  });
});
