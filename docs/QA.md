# 品質確認

## 実施済み

- WordPress 7.1.2 / PHP 8.3とWordPress 6.6.9 / PHP 7.4.31の一時環境にテーマと2プラグインを導入・有効化
- ブロックテーマのフロントページ表示を確認
- セクションパターンのレンダリングを確認
- ROBSE CTAのブロック登録とフロント側サーバーレンダリングを確認
- GutenbergインサーターへのCTAブロック登録を確認
- CMS設定画面の表示、公開カスタム投稿タイプの選択肢表示を確認
- AI設定画面とページ案作成画面の表示を確認
- OpenAI APIを外部呼び出しせず、WordPressのローカルHTTPモックでREST同意チェック、ページ案受信、固定ページ下書き作成、入力検証を確認
- PHP/JSON/JavaScriptの構文確認
- ZIPの構成と展開確認
- ROBSE ONE Blocks：公式Plugin Checkでエラーなし。WordPress.orgへ0.2.1を提出し、自動スキャンPass、審査待ち（2026-09-23）
- ROBSE ONE AI：公式Plugin Checkでエラーなし、OpenAI API直接連携に関する警告1件。WordPress 6.6/6.7の互換性を保つための意図的な実装で、理由を readme と docs/AI-PRIVACY.md に記載。Blocksの審査中のため未提出
- ROBSE ONE Theme：WordPress.orgへ提出済み。審査チケット292750。自動スキャンPass。提出済みの版に対するメジャーバージョン表記の推奨を受け、作業用パッケージは `Tested up to: 7.1` に更新済み

## 追加確認

- 実サイトLPを390px幅で確認し、横方向のはみ出しなし（document/body幅375px、viewport390px）を確認
- 実サイトのROBSE ONEメニューリンクと問い合わせ種別「ROBSE ONE（テーマ・先行案内）」を公開HTMLで確認。フォーム定義と隠し項目を維持し、Contact Form 7の正式な保存API経由で反映
- WordPress PlaygroundのWordPress 7.1.2でCTAブロックをインサーターから追加できることを確認。編集キャンバスが空白のため、属性編集・保存の動作は未確認

## リリース前に残る確認


- Editorロールなど管理者以外でのCMSメニュー表示、権限チェック
- GutenbergでCTA属性の編集欄が表示され、変更を保存できるか再確認
- WordPress Theme Unit Test Dataを使った投稿・コメント・画像の確認
- スマートフォン幅、キーボード操作、スクリーンリーダー、色コントラストの監査
- Chrome、Safari、Firefoxでの表示確認
- PageSpeed/Lighthouseを使った実サイト計測
- 実際のOpenAIアカウントとAPIキーを用いた生成確認（利用者の同意・費用負担が必要）
- WordPress.orgからのTheme/Blocks審査結果に応じた修正と更新提出

このプロジェクトの開発環境には、本番サイト接続情報やOpenAI APIキーを含めていません。
