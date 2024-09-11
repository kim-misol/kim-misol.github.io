module.exports = [
  {
    ignores: ['dist/**'], // 무시할 디렉토리나 파일을 지정
  },
  {
    files: ['src/**/*.{ts,tsx}'], // ts와 tsx 파일 모두 검사
    languageOptions: {
      ecmaVersion: 2020,
      sourceType: 'module',
    },
    plugins: {
      prettier: eslintPluginPrettier, // Prettier 플러그인을 객체 형식으로 설정
    },
    extends: [
      'eslint:recommended',
      'plugin:@typescript-eslint/recommended', // TypeScript 린트 규칙 사용
      'plugin:prettier/recommended' // Prettier와 ESLint 통합
    ],
    rules: {
      'prettier/prettier': 'error', // Prettier 규칙 위반 시 에러로 처리
      'semi': ['error', 'always'], // 세미콜론 규칙
      'no-unused-vars': 'warn', // 사용되지 않은 변수는 경고
      '@typescript-eslint/no-unused-vars': 'warn', // TypeScript 관련 규칙
    },
  },
];
