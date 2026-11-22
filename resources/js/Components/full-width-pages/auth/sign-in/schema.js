import { isEmail } from '@/composables/useFormValidation'

export const validateSignIn = (data) => {
  const errors = {}

  if (!data.email.trim()) errors.email = 'Email is required'
  else if (!isEmail(data.email.trim())) errors.email = 'Please enter a valid email address'

  if (!data.password) errors.password = 'Password is required'

  return errors
}