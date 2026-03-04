export type UserRole = 'user' | 'moderator' | 'admin';

export type User = {
    id: number;
    name: string;
    email: string;
    email_verified_at: string | null;
    role: UserRole;
    active: boolean;
    phone?: string | null;
    birthdate?: Date | null;
    sex?: string | null;
    bio?: string | null;
    location?: string | null;
    address?: string | null;
    website?: string | null;
    avatar?: string;
    created_at: string;
    updated_at: string;
    [key: string]: unknown;
};

export type Auth = {
    user: User;
};

export type TwoFactorConfigContent = {
    title: string;
    description: string;
    buttonText: string;
};
