import Echo from 'laravel-echo'
import Pusher from 'pusher-js'

window.Pusher = Pusher

const echo = new Echo({
    broadcaster: 'pusher',
    key: import.meta.env.VITE_PUSHER_APP_KEY,
    cluster: import.meta.env.VITE_PUSHER_APP_CLUSTER,
    forceTLS: true,
    authEndpoint: '/broadcasting/auth',
    auth: {
        headers: {
            'Accept': 'application/json',
        },
        withCredentials: true,
    },
    enabledTransports: ['ws', 'wss'],
})

// Debug connection status
// echo.connector.pusher.connection.bind('connected', () => {
//     console.log('✅ Pusher connected successfully')
// })

// echo.connector.pusher.connection.bind('disconnected', () => {
//     console.log('❌ Pusher disconnected')
// })

// echo.connector.pusher.connection.bind('error', (err) => {
//     console.error('❌ Pusher connection error:', err)
// })

// echo.connector.pusher.connection.bind('state_change', (states) => {
//     console.log('Pusher state changed:', states.previous, '->', states.current)
// })

export default echo
