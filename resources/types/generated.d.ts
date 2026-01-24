declare namespace App.Data {
    export type TeamData = {
        id: number;
        name: string;
        createdAt: string | null;
        updatedAt: string | null;
        logoUrl: string | null;
    };
    export type TeamTournamentData = {
        id: number;
        name: string;
        strength: number;
        logoUrl: string | null;
    };
    export type TournamentData = {
        id: number;
        name: string;
        status: App.Enums.TournamentStatus;
        userId: number;
        teams: Array<App.Data.TeamTournamentData> | null;
    };
}
declare namespace App.Enums {
    export type TournamentStatus = 'created' | 'in_progress' | 'completed';
}
