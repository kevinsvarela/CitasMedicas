import { Component, inject, OnInit, signal } from '@angular/core';
import {
  form,
  FormField,
  FormRoot,
  maxLength,
  minLength,
  pattern,
  required,
  RootFieldContext,
  validate,
} from '@angular/forms/signals';
import { MatButtonModule } from '@angular/material/button';
import { MAT_DIALOG_DATA, MatDialogModule, MatDialogRef } from '@angular/material/dialog';
import { MatDividerModule } from '@angular/material/divider';
import { MatFormFieldModule } from '@angular/material/form-field';
import { MatInputModule } from '@angular/material/input';
import { MedicoCreateInput } from '../../../shared/models/medico-model';

@Component({
  imports: [MatDialogModule, MatDividerModule, MatButtonModule, 
            MatFormFieldModule, MatInputModule, FormRoot, FormField],
  selector: 'app-frm-medico',
  styleUrl: './frm-medico.css',
  templateUrl: './frm-medico.html',
})
export class FrmMedico implements OnInit {
  titulo: string = "";
  dialogRef = inject(MatDialogRef<FrmMedico>);
  private readonly data =  inject(MAT_DIALOG_DATA);


  objMedico = signal<MedicoCreateInput>({
    especialidad_id: 0,
    nombre_completo: '', 
    licencia: '',
    telefono: '',
    username: '',
    password: ''
  });

frmMedico = form(this.objMedico, (s) => {
  required(s.especialidad_id, {message: "Especialidad es requerida"});
  validate(s.especialidad_id, (context: RootFieldContext<number>): { kind: string; message: string } | null => {
      const value = context.value();
      const numericValue = Number(value);
      if (isNaN(numericValue) || !Number.isInteger(numericValue) || numericValue <= 0) {
        return { kind: 'invalid', message: 'Especialidad inválida' };
      }
      return null;
    });
  required(s.nombre_completo, {message: "El nombre es requerido"});
  minLength(s.nombre_completo, 11, {message: 'El nombre debe tener al menos 11 caracteres.'});
  maxLength(s.nombre_completo, 150, {message: 'El nombre no puede superar 150 caracteres.'});
  pattern(s.nombre_completo, /^[A-Za-zÑñÁÉÍÓÚáéíóú]{2,}(?: [A-Za-zÑñÁÉÍÓÚáéíóú]{2,}){1,3}$/, {
    message: 'Ingrese de 2 a 4 nombres, usando solo letras y espacios.',
  });
  required(s.licencia, {message: "Licencia es requerida"});
  maxLength(s.licencia, 50, {message: 'La licencia no puede superar 50 caracteres.'});
  required(s.username!, {message: "Nombre de usuario es requerido"});
  pattern(s.username!, /^[a-z0-9]{8,16}$/, {
    message: 'El usuario debe tener de 8 a 16 letras minúsculas o números.',
  });
  required(s.password!, {message: "Contraseña es requerida"});
  pattern(s.password!, /^(?=.*\d)(?=.*[a-z])(?=.*[A-Z])(?=.*[#$@])[\s\S]{8,64}$/, {
    message: 'La contraseña debe tener 8-64 caracteres, mayúscula, minúscula, número y #, $ o @.',
  });
  pattern(s.telefono!, /^[2-9][0-9]{3}-[0-9]{4}$/, {
    message: 'El teléfono debe tener el formato ####-####.',
    when: (context) => context.value() !== '',
  });
})

  guardar(event: Event): void {
    event.preventDefault();
    this.frmMedico().markAsTouched();

    if (this.frmMedico().invalid()) {
      return;
    }

    this.dialogRef.close(this.objMedico());
  }

  ngOnInit(): void {
    this.titulo = this.data.title;
  }
}
