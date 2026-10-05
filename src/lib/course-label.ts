export function formatSemesterClassLabel(
  semesterName?: string | null,
  targetSemester?: number | string | null,
  classGroup?: string | null,
) {
  const classIdentity = [
    targetSemester === null || targetSemester === undefined ? '' : String(targetSemester).trim(),
    classGroup?.trim() ?? '',
  ].filter(Boolean).join('/');
  return [semesterName?.trim(), classIdentity].filter(Boolean).join(', ');
}
