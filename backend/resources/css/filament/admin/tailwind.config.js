import path from 'path'
import { fileURLToPath } from 'url'
import preset from '../../../../vendor/filament/filament/tailwind.config.preset'

const root = path.resolve(path.dirname(fileURLToPath(import.meta.url)), '../../../..')

export default {
    presets: [preset],
    content: [
        path.join(root, 'app/Filament/**/*.php'),
        path.join(root, 'resources/views/filament/**/*.blade.php'),
        path.join(root, 'vendor/filament/**/*.blade.php'),
    ],
}
