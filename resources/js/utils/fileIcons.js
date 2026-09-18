import {
    DocumentIcon, DocumentTextIcon, PhotoIcon, TableCellsIcon,
    PresentationChartBarIcon, ArchiveBoxIcon, FolderIcon,
} from '@heroicons/vue/24/outline';

export function iconFor(mimeType, isFolder = false) {
    if (isFolder) return FolderIcon;
    if (!mimeType) return DocumentIcon;
    if (mimeType.startsWith('image/')) return PhotoIcon;
    if (mimeType === 'application/pdf' || mimeType.includes('word')) return DocumentTextIcon;
    if (mimeType.includes('sheet') || mimeType.includes('excel')) return TableCellsIcon;
    if (mimeType.includes('presentation') || mimeType.includes('powerpoint')) return PresentationChartBarIcon;
    if (mimeType.includes('zip')) return ArchiveBoxIcon;
    return DocumentIcon;
}
