export class User {
    id : string;
    username: string;
    rol_id : number;
    constructor(usr? : User) {
        this.id = usr !== undefined ? usr.id : ''
        this.username = usr !== undefined ? usr.username : ''
        this.rol_id = usr !== undefined ? usr.rol_id : -1;
    }
}