export type ExistingCourseModule = {
  id: number;
  meetingNumber: number;
  publishedAt: Date | null;
};

export type CourseModuleChangePlan = {
  deletedIds: number[];
  resetIds: number[];
};

export function resetCourseModule(module: Pick<ExistingCourseModule, 'meetingNumber'>, updatedAt = new Date()) {
  return {
    title: `Modul ${module.meetingNumber}`,
    description: null,
    moduleDriveLink: null,
    deadline: null,
    publishedAt: null,
    updatedAt,
  };
}

/** Validates that every existing module is updated, reset, or explicitly removed. */
export function planCourseModuleChanges(
  existingModules: ExistingCourseModule[],
  submittedIds: number[],
  deletedIds: number[],
  resetIds: number[],
): CourseModuleChangePlan {
  const existingById = new Map(existingModules.map((module) => [module.id, module]));
  const submitted = new Set(submittedIds);
  const deleted = new Set(deletedIds);
  const reset = new Set(resetIds);

  if (existingById.size !== existingModules.length || submitted.size !== submittedIds.length ||
      deleted.size !== deletedIds.length || reset.size !== resetIds.length) {
    throw new Error('Daftar modul mengandung ID duplikat.');
  }

  for (const id of submitted) {
    if (!existingById.has(id)) throw new Error('Daftar modul kelas berubah. Muat ulang halaman lalu coba lagi.');
  }
  for (const id of deleted) {
    if (!existingById.has(id) || submitted.has(id)) {
      throw new Error('Daftar modul kelas berubah. Muat ulang halaman lalu coba lagi.');
    }
  }
  for (const id of reset) {
    const existingModule = existingById.get(id);
    if (!existingModule || deleted.has(id) || !submitted.has(id) || !existingModule.publishedAt) {
      throw new Error('Hanya modul yang sudah dibuka yang dapat dikembalikan ke Coming soon.');
    }
  }

  if (submitted.size + deleted.size !== existingById.size) {
    throw new Error('Daftar modul kelas berubah. Muat ulang halaman lalu coba lagi.');
  }

  return { deletedIds: [...deleted], resetIds: [...reset] };
}
