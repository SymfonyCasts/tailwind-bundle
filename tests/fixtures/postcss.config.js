module.exports = {
  plugins: [
    {
      postcssPlugin: 'dummy',
      Once (root) {
        root.append('.dummy {}')
      },
    },
  ],
}
