declare namespace App.Data {
    export type MatchData = {
        id: number;
        tournamentId: number;
        homeTeamId: number;
        awayTeamId: number;
        homeScore: number | null;
        awayScore: number | null;
        week: number;
        isPlayed: boolean;
        homeTeam: App.Data.TeamData | null;
        awayTeam: App.Data.TeamData | null;
    };
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
