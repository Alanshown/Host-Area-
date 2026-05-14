export const useAuth = () => {
  const user = useState('auth_user', () => null)
  const isLoggedIn = computed(() => !!user.value)
  const config = useRuntimeConfig()
  const apiBase = config.public.apiBase
  const loginRequest = useState('auth_login_request', () => null)
  const registerRequest = useState('auth_register_request', () => null)

  const clearClientAuth = () => {
    setUser(null)

    if (!process.client) return

    localStorage.removeItem('auth_token')
    localStorage.removeItem('auth_user')
  }

  const setUser = (value) => {
    user.value = value

    if (!process.client) return

    if (value) {
      localStorage.setItem('auth_user', JSON.stringify(value))
    } else {
      localStorage.removeItem('auth_user')
    }
  }

  const initAuth = () => {
    if (process.client) {
      const stored = localStorage.getItem('auth_user')
      if (stored) {
        try { user.value = JSON.parse(stored) } catch {}
      }
    }
  }

  const refreshMe = async () => {
    if (!process.client) return null

    const token = localStorage.getItem('auth_token')
    if (!token) return null

    try {
      const data = await $fetch(`${apiBase}/auth/me`, {
        headers: { Authorization: `Bearer ${token}` },
      })
      setUser(data.user ?? data.data ?? null)
      return data
    } catch (error) {
      // If banned (403 with force_logout), clean up and redirect
      if (error?.response?.status === 403) {
        const body = error.response._data || error.data || {}
        if (body.force_logout || body.banned_until) {
          clearClientAuth()
          if (process.client) {
            alert(body.message || '你的账号已被封禁')
          }
          return null
        }
      }

      clearClientAuth()
      return null
    }
  }

  const login = async (email, password) => {
    if (loginRequest.value) {
      return loginRequest.value
    }

    loginRequest.value = $fetch(`${apiBase}/auth/login`, {
      method: 'POST',
      body: { email, password },
    })
      .then((data) => {
        setUser(data.user)
        if (process.client) {
          localStorage.setItem('auth_token', data.token)
        }
        return data
      })
      .finally(() => {
        loginRequest.value = null
      })

    return loginRequest.value
  }

  const register = async (username, email, password, passwordConfirmation) => {
    if (registerRequest.value) {
      return registerRequest.value
    }

    registerRequest.value = $fetch(`${apiBase}/auth/register`, {
      method: 'POST',
      body: { username, email, password, password_confirmation: passwordConfirmation },
    })
      .then((data) => {
        setUser(data.user)
        if (process.client) {
          localStorage.setItem('auth_token', data.token)
        }
        return data
      })
      .finally(() => {
        registerRequest.value = null
      })

    return registerRequest.value
  }

  const logout = async () => {
    if (process.client) {
      const token = localStorage.getItem('auth_token')
      if (token) {
        try {
          await $fetch(`${apiBase}/auth/logout`, {
            method: 'POST',
            headers: { Authorization: `Bearer ${token}` },
          })
        } catch {}
      }
    }
    clearClientAuth()
  }

  return { user, isLoggedIn, initAuth, refreshMe, setUser, login, logout, register }
}
