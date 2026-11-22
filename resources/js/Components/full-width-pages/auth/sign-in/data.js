import { GoogleIcon, TwitterXIcon } from '@/icons/layout'

export const socialProviders = [
  {
    id: 'google',
    name: 'Google',
    icon: GoogleIcon,
    iconClass: 'size-5',
  },
  {
    id: 'x',
    name: 'X',
    icon: TwitterXIcon,
    iconClass: 'size-5 fill-current',
  },
]

export const socialAuthData = socialProviders