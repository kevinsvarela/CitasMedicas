import { inject, Service, signal } from '@angular/core';
import { HttpClient } from '@angular/common/http';
import { environment } from '../../../environments/environment';
import { TokenService } from './token-service';
import { Router } from '@angular/router';
import { User } from '../models/User';
import { IToken, LoginForm } from '../models/interfaces';
import { tap, map, catchError, of } from 'rxjs';

const servidor = environment.servidor + '/auth';

@Service()
export class AuthService {
    private http = inject(HttpClient);
    private readonly srvTokens = inject(TokenService);
    private readonly router = inject(Router);
    public userActualS = signal(new User);
    
    public login(datos : LoginForm) {
        return this.http.post<IToken>(`${servidor}/login`, datos)
        .pipe(
            tap((tokens) => {
            this.doLogin(tokens);
            }),
            map(() => true),
            catchError((error) => {
            return of (error.status);
            })
        );
    }

    public loggout() {
        if (this.isLoggedIn()) {
            this.http.delete(`${servidor}/loggout/${this.userActual.id}`,{})
                .subscribe();
            this.doLoggOut();    //Esto fuera del if
       }
    }

    public isLoggedIn() : boolean {
        return !!this.srvTokens.token && !this.srvTokens.jwtTokenExp();
    }
    
    doLogin(tokens : any) {
        this.srvTokens.setTokens(tokens);
        this.userActualS.set(this.userActual);
    }

    doLoggOut() {
        //Eliminar los tokens
        if (this.srvTokens.token) {
        this.srvTokens.eliminarTokens();
        }
        //limpiar datos globales para usuario y rol
        this.userActualS.set(this.userActual);
        //navegar a la página de login
        this.router.navigate(['/login']);
   }

    private get userActual() : User {
        if (!this.srvTokens.token) {
            return new User();
        }else{
            const tokenD = this.srvTokens.decodeToken();
            return new User({id: tokenD.sub, username: tokenD.username, rol_id: tokenD.rol_id});
        }
    }
}