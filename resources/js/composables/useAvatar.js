import { computed } from 'vue'

/**
 * Composable for handling user avatars with fallback to first letter
 * @param {Object} user 
 * @returns {Object}
 */
export function useAvatar(user) {
    const getAvatarUrl = computed(() => {
        if (!user.value) return null

        if (user.value.profile_image) {
            if (user.value.profile_image.startsWith('http')) {
                return user.value.profile_image
            }
            return `http://127.0.0.1:8000/storage/${user.value.profile_image}`
        }

        return null
    })

    const getInitials = computed(() => {
        if (!user.value || !user.value.name) return '?'
        const name = user.value.name.trim()
        if (name.length === 0) return '?'
        return name.charAt(0).toUpperCase()
    })

    const getAvatarColor = computed(() => {
        if (!user.value || !user.value.name) return '#6B7280'

        const name = user.value.name.trim()
        const colors = [
            '#EF4444',
            '#F59E0B',
            '#10B981',
            '#3B82F6',
            '#8B5CF6',
            '#EC4899',
            '#14B8A6',
            '#F97316',
        ]

        const hash = name.split('').reduce((acc, char) => {
            return char.charCodeAt(0) + ((acc << 5) - acc)
        }, 0)

        return colors[Math.abs(hash % colors.length)]
    })

    return {
        getAvatarUrl,
        getInitials,
        getAvatarColor,
    }
}

/**
 * Helper function to get avatar URL from user object
 * @param {Object} user 
 * @returns {string|null} 
 */
export function getAvatarUrl(user) {
    if (!user) return null

    if (user.profile_image) {
        if (user.profile_image.startsWith('http')) {
            return user.profile_image
        }
        return `http://127.0.0.1:8000/storage/${user.profile_image}`
    }

    return null
}

/**
 * Helper function to get initials from user object
 * @param {Object} user 
 * @returns {string}
 */
export function getInitials(user) {
    if (!user || !user.name) return '?'
    const name = user.name.trim()
    if (name.length === 0) return '?'
    return name.charAt(0).toUpperCase()
}

/**
 * Helper function to get avatar color from user object
 * @param {Object} user 
 * @returns {string}
 */
export function getAvatarColor(user) {
    if (!user || !user.name) return '#6B7280'

    const name = user.name.trim()
    const colors = [
        '#EF4444',
        '#F59E0B',
        '#10B981',
        '#3B82F6',
        '#8B5CF6',
        '#EC4899',
        '#14B8A6',
        '#F97316',
    ]

    const hash = name.split('').reduce((acc, char) => {
        return char.charCodeAt(0) + ((acc << 5) - acc)
    }, 0)

    return colors[Math.abs(hash % colors.length)]
}

