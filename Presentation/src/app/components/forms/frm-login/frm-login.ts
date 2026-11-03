import { Component, signal, inject } from '@angular/core';
import { MatButtonModule } from '@angular/material/button';
import { MatCardModule } from '@angular/material/card';
import { MatFormField, MatFormFieldModule } from '@angular/material/form-field';
import { MatIconModule } from '@angular/material/icon';
import { FormField, FormRoot, form, required } from '@angular/forms/signals';
import { MatInput } from "@angular/material/input";
import { LoginForm } from '../../../shared/models/interfaces';
import { MatDialogRef } from '@angular/material/dialog';
import { AuthService } from '../../../shared/services/auth-service';
import { Router } from '@angular/router';

@Component({
  selector: 'app-frm-login',
  imports: [MatCardModule, MatIconModule, MatButtonModule,
    MatFormFieldModule, MatFormField, FormRoot, MatInput, FormField],
  templateUrl: './frm-login.html',
  styleUrl: './frm-login.css',
})
export class FrmLogin {

  private readonly srvAuth = inject(AuthService);
  private readonly router = inject(Router);

  dialogRef = inject(MatDialogRef<FrmLogin>)
  public mostrarError = signal(false);
  objLogin = signal<LoginForm>( {
    username : '',
    password: ''
  });

  frmLogin = form(this.objLogin, (s)=> {
    required(s.username, { message: 'Usuario es requerido'});
    required(s.password, { message: 'Contraseña es requerida'});
    
  },
  {
    submission: {
      action: async () => {
        this.loggin(this.objLogin());
      }
    }
  }
);

loggin(datos : LoginForm) {
     this.srvAuth.login(datos)
       .subscribe({
         next: (res) => {
           if (res && res != 401) {
            this.dialogRef.close(true);
             this.router.navigate(['/home']);
           }else{
              this.mostrarError.set(true);
           }
         },
       })
 }

}