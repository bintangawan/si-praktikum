export const USER_ROLES = ['Dosen', 'Mahasiswa', 'Laboran', 'Aslab'] as const;
export type UserRole = (typeof USER_ROLES)[number];

export const ROLE_LABELS: Record<UserRole, string> = {
  Dosen: 'Dosen',
  Mahasiswa: 'Mahasiswa',
  Laboran: 'Laboran',
  Aslab: 'Aslab',
};
