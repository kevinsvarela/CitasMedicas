import { inject, Injectable,signal } from '@angular/core';
import {MedicoModel} from '../models/medico-model';
import { HttpClient, httpResource } from '@angular/common/http';
import { environment } from '../../../environments/environment.development';

 type ParametroMedico = Record<string, string | number | boolean>;
 
 const _SERVIDOR = environment.servidor;

 interface RespuestaMedico {
    data: MedicoModel[];
    pagination:{
          offset: number;
            limit: number;
            total: number;
    };

 }
@Injectable({providedIn: 'root'})
export class MedicoService {
   private readonly http = inject(HttpClient);
   private readonly parametros = signal<ParametroMedico | null>(null);

   readonly medicos = httpResource<RespuestaMedico>(()=>{
    const parametros = this.parametros();
    if (parametros === null){
        return undefined;
    }
    return{
        url: `${_SERVIDOR}/medicos/filter/0/10`,
        params: parametros,
    };
 });
 filtrar(parametros: ParametroMedico): void{
    this.parametros.set(parametros);
 }
 guardar(datos: MedicoModel, id? : number){
      delete datos.id;

      if(id){//Modificando al cliente
         return this.http.put<any>(`${_SERVIDOR}/medicos/${id}`, datos);
      }
       else{//Nuevo cliente
         return this.http.post<any>(`${_SERVIDOR}/medicos`, datos);
       }
  }
}