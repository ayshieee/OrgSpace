import {
    AcademicCapIcon,
    BuildingLibraryIcon,
    UserGroupIcon,
    TrophyIcon,
    MusicalNoteIcon,
    HeartIcon,
    BuildingOffice2Icon,
} from '@heroicons/vue/24/outline';

const ICONS = {
    academic: AcademicCapIcon,
    student_council: BuildingLibraryIcon,
    fraternity_sorority: UserGroupIcon,
    sports_club: TrophyIcon,
    performing_arts: MusicalNoteIcon,
    community_service: HeartIcon,
    other: BuildingOffice2Icon,
};

export function orgTypeIcon(type) {
    return ICONS[type] ?? BuildingOffice2Icon;
}
