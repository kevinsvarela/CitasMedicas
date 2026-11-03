import { HttpInterceptorFn } from '@angular/common/http';
import { inject } from '@angular/core';
import { TokenService } from '../../services/token-service';

export const jwtInterceptor: HttpInterceptorFn = (req, next) => {
    const token = inject(TokenService).token;
     //"eyJ0eXAiOiJKV1QiLCJhbGciOiJIUzI1NiJ9.eyJpc3MiOiJjaXRhcy1tZWRpY2FzLWJ1c2luZXNzIiwiaWF0IjoxNzkzNTA5ODEyLCJleHAiOjE3OTM1MTM0MTIsInN1YiI6IjEiLCJ1c2VybmFtZSI6ImtldmluMTIzIiwicm9sX2lkIjoyfQ.v5vAGTgcTIEf3bEftJzR6CLhBz0tvusbj1RVFe31CRk";
    if (token) {
        req = req.clone({
            setHeaders: {
                Authorization: `Bearer ${token}`
            }
        })
    }
    return next(req);
};