import { Routes } from '@angular/router';
import { authGuard } from './shared/helpers/guards/auth-guard';
import { Roles } from './shared/models/roles';

export const routes: Routes = [
  { path: '', redirectTo: 'home', pathMatch: 'full'},
  { path: 'home',
    canActivate: [authGuard], data: {roles: [Roles.Admin, Roles.Medico, Roles.Paciente]},
    loadComponent: () => import('./components/home-component/home-component')
      .then(m => m.HomeComponent)
  },
  { path: 'medico',
    loadComponent: () => import('./components/medico-component/medico-component')
      .then(m => m.MedicoComponent)
  },
  { path: 'paciente',
    loadComponent: () => import('./components/paciente-component/paciente-component')
      .then(m => m.PacienteComponent)
  },
  { path: 'citas',
    loadComponent: () => import('./components/citas-component/citas-component')
      .then(m => m.CitasComponent)
  },
  { path: 'login',
    canActivate: [authGuard], data: {roles: [Roles.Admin, Roles.Medico, Roles.Paciente]},
    loadComponent: () => import('./components/login-component/login-component')
      .then(m => m.LoginComponent)
  },
  { path: '**',
    loadComponent: () => import('./components/error404-component/error404-component')
      .then(m => m.Error404Component)
  },
  
];