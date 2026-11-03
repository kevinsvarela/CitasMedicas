export interface MedicoModel {
  id?: number;
  usuario_id?: number;
  especialidad_id: number;
  especialidad_nombre: string;
  nombre_completo: string;
  licencia: string;
  telefono?: string;
  username?: string;
  password?: string;
}

export type MedicoCreateInput = Omit<MedicoModel, 'id' | 'usuario_id' | 'especialidad_nombre'>;
export type MedicoUpdateInput = Omit<MedicoModel, 'usuario_id' | 'especialidad_nombre' | 'licencia'> & {id:number};

