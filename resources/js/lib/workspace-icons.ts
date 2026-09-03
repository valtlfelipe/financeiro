import {
    BriefcaseBusiness,
    Building2,
    GraduationCap,
    HeartHandshake,
    House,
    PiggyBank,
    Plane,
    UsersRound,
} from '@lucide/vue';
import type { Component } from 'vue';

export const workspaceIconNames = [
    'house',
    'building-2',
    'briefcase-business',
    'users-round',
    'heart-handshake',
    'piggy-bank',
    'plane',
    'graduation-cap',
] as const;

export type WorkspaceIconName = (typeof workspaceIconNames)[number];

const workspaceIcons: Record<WorkspaceIconName, Component> = {
    house: House,
    'building-2': Building2,
    'briefcase-business': BriefcaseBusiness,
    'users-round': UsersRound,
    'heart-handshake': HeartHandshake,
    'piggy-bank': PiggyBank,
    plane: Plane,
    'graduation-cap': GraduationCap,
};

export function workspaceIcon(icon: string | null | undefined): Component {
    return workspaceIcons[icon as WorkspaceIconName] ?? House;
}
