import { Component, HostListener, inject, signal } from '@angular/core';
import { RouterModule, RouterOutlet } from '@angular/router';
import { MatToolbarModule } from '@angular/material/toolbar';
import { MatButtonModule } from '@angular/material/button';
import { MatIconModule } from '@angular/material/icon';
import { MatListModule } from '@angular/material/list';
import { MatSidenavModule } from '@angular/material/sidenav';
import { MatMenuModule } from '@angular/material/menu';
import { MatTooltipModule } from '@angular/material/tooltip';
import { MatDividerModule } from '@angular/material/divider';
import { AuthService } from './shared/services/auth-service';

type MenuItem = {
  label : string;
  icon : string;
  route : string;
  rol : number[];
}

@Component({
  imports: [RouterOutlet, RouterModule, MatToolbarModule, MatButtonModule, MatIconModule,
    MatTooltipModule, MatMenuModule, MatSidenavModule, MatListModule, MatDividerModule
  ],
  selector: 'app-root',
  styleUrl: './app.css',
  templateUrl: './app.html',
})
export class App {
  
  protected readonly title = signal('citas_medicas');
  protected readonly authService = inject(AuthService);


menuItems: MenuItem[] = [
    {label: 'Inicio', icon: 'home', route:'home', rol :[0, 1 , 2, 3]},
    {label: 'Medicos', icon: 'group', route:'medico', rol : [0, 1, 3]},
    {label: 'Pacientes', icon: 'person', route:'paciente', rol :[0, 1]},
    {label: 'Nueva Cita', icon: 'calendar_month', route:'citas', rol :[0, 1]},
    {label: 'Cancelar Cita', icon: 'event_busy', route: 'cancelarcita', rol: [0, 1, 2]} ];

    @HostListener('window:beforeunload') unloadHandler() {
    this.loggout();
    }

    @HostListener('window:popstate')
    onBrowserBack(): void {
      this.loggout();
    }

    loggout(){
      this.authService.loggout();
    }

    

    

}
