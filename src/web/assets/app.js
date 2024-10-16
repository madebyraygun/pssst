const cfTsWidget = document.querySelector('.cf-turnstile');

if (localStorage.getItem('theme')) {
  document.documentElement.setAttribute('data-theme', localStorage.getItem('theme'))
  if (cfTsWidget) {
    cfTsWidget.setAttribute('data-theme', localStorage.getItem('theme'))
  }
} else if (window.matchMedia('(prefers-color-scheme: dark)').matches) {
  document.documentElement.setAttribute('data-theme', 'dark')
  if (cfTsWidget) {
    cfTsWidget.setAttribute('data-theme', 'dark')
  }
}

let currentTheme = document.documentElement.getAttribute('data-theme') || (window.matchMedia('(prefers-color-scheme: dark)').matches ? 'dark' : 'light')
const contrast = document.querySelector('.contrast')
contrast.addEventListener('click', function() {
  currentTheme = currentTheme === 'dark' ? 'light' : 'dark'
  document.documentElement.setAttribute('data-theme', currentTheme)
  if (cfTsWidget) {
    cfTsWidget.setAttribute('data-theme', currentTheme)
  }
  localStorage.setItem('theme', currentTheme)
})
