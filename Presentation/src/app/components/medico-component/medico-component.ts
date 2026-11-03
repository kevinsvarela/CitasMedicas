import { Component, effect, inject, OnInit, signal } from '@angular/core';
import { HttpErrorResponse } from '@angular/common/http';
import {MatTableModule} from '@angular/material/table';
import { MedicoService } from '../../shared/services/medico-service';
import { MedicoModel } from '../../shared/models/medico-model';
import { MatCardModule } from '@angular/material/card';
import { MatButtonModule } from '@angular/material/button';
import { MatIconModule } from '@angular/material/icon';
import { MatDialog, MatDialogModule } from '@angular/material/dialog';
import { FrmMedico } from '../forms/frm-medico/frm-medico';


@Component({
  imports: [MatTableModule, MatCardModule,MatButtonModule, MatIconModule,
            MatDialogModule
],
  selector: 'app-medico-component',
  styleUrl: './medico-component.css',
  templateUrl: './medico-component.html',
})
export class MedicoComponent implements OnInit {
  displayedColumns: string[] = 
  ['id', 'nombre_completo', 'especialidad_nombre', 'licencia', 'telefono', 'botonera'];
  
  dataSource = signal<MedicoModel[]>([]);

  srvMedico = inject(MedicoService);

  dialog = inject(MatDialog);

  constructor() {
    effect(() => {
    const respuesta = this.srvMedico.medicos.value();
    if(respuesta !== undefined) {
      //console.log(respuesta.data);
      this.dataSource.set(respuesta.data);
    }
    }); 
  }


  public mostrarDialogo(titulo: string, datos? : MedicoModel, info? : boolean){
    const dialogRef= this.dialog.open(FrmMedico, {
       width: '50vw',
       maxWidth: '35rem',
      data: {
        title: titulo,
        data: datos,
        info: info
      },
      disableClose: true
    });

    dialogRef.afterClosed()
      .subscribe((objMedico)=>{
        if(objMedico === undefined){
          return;
        }
        this.srvMedico.guardar(objMedico)
        .subscribe({
          complete : () =>{
            this.srvMedico.medicos.reload();
            alert('Datos Guardados');
          },
          error: (error: HttpErrorResponse) => {
            alert(this.mensajeError(error));
          },
        });
      });
  }

  private mensajeError(error: HttpErrorResponse): string {
    const payload: unknown = error.error;
    if (typeof payload === 'object' && payload !== null) {
      const respuesta = payload as Record<string, unknown>;
      if (typeof respuesta['error'] === 'string') {
        return respuesta['error'];
      }

      const errores = respuesta['errors'];
      if (typeof errores === 'object' && errores !== null) {
        const mensajes = Object.values(errores as Record<string, unknown>)
          .filter((mensaje): mensaje is string => typeof mensaje === 'string');
        if (mensajes.length > 0) {
          return mensajes.join('\n');
        }
      }
    }

    return `No se pudieron guardar los datos (HTTP ${error.status}).`;
  }
  
  //Fin de mostrar dialogo  

  onCreate(): void{
    this.mostrarDialogo('Nuevo médico');
    //console.log('Creacion de medico')
  }

  onEdit(idMedico : number){
    console.log('Editando medico', idMedico);
  }

  onDelete(idMedico : number){
    console.log('Eliminar medico', idMedico);
  }

  onInfo(idMedico : number){
    console.log('Ver informacion medico', idMedico);
  }

  onResetPassword(idMedico : number){
    console.log('Resetear contraseña de ', idMedico);
  }
  
  ngOnInit(): void {
    this.srvMedico.filtrar( {nombre: '', cedula: '', especialidad: ''});
  }
}