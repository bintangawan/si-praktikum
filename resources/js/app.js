import './bootstrap';

import Alpine from 'alpinejs';

import { driveSubmission } from './drive-link';
import { studentSearch } from './student-search';
Alpine.data('driveSubmission', driveSubmission);
Alpine.data('studentSearch', studentSearch);
window.Alpine = Alpine;

Alpine.start();
