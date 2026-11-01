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
    export type StandingData = {
        id: number;
        tournamentId: number;
        teamId: number;
        played: number;
        won: number;
        drawn: number;
        lost: number;
        goalsFor: number;
        goalsAgainst: number;
        goalDifference: number;
        points: number;
        team: App.Data.TeamData | null;
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
        currentWeek: number;
        totalWeeks: number;
        teams: Array<App.Data.TeamTournamentData> | null;
    };
}
declare namespace App.Data.Tournament {
    export type StoreTournamentData = {
        name: string;
        userId: number;
        teams: Array<App.Data.Tournament.TeamSelectionData>;
    };
    export type TeamSelectionData = {
        id: number;
        strength: number;
    };
    export type UpdateTournamentData = {
        name: string;
        teams: Array<App.Data.Tournament.TeamSelectionData>;
    };
}
declare namespace App.Enums {
    export type TournamentStatus =
        | 'created'
        | 'in_progress'
        | 'simulating'
        | 'completed';
}
