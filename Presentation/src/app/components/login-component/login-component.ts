import { Component, inject } from '@angular/core';
import { MatDialog } from '@angular/material/dialog';
import { FrmLogin } from '../forms/frm-login/frm-login';
import { Router } from '@angular/router';

@Component({
  imports: [],
  selector: 'app-login-component',
  styleUrl: './login-component.css',
  templateUrl: './login-component.html',
})
  export class LoginComponent {
  private readonly dialogo = inject(MatDialog);
  private readonly router = inject(Router);

  private mostrarDialogo(){
    const dialogRef = this.dialogo.open(FrmLogin,{
      width: '100%',
      maxWidth: '400px',
      disableClose: true
    });
}

  ngOnInit(): void{
    this.mostrarDialogo();
  }
}