<?php
/* =========================================================
   2ch風掲示板 サンプル (index.php 1ファイル完結版)
   ※このコードはセキュリティ面などを一切考慮していない
     「デザイン確認用のサンプル」です。
     実際に公開・運用する場合は必ず改変してください。
   ========================================================= */

// ---------------------------------------------------------
// 設定
// ---------------------------------------------------------
$BOARD_TITLE = "掲示板タイトル(変更してください)";
$BOARD_DESC  = "ここに板の説明を書いてください。自由に編集してください。";
$DATA_FILE   = __DIR__ . "/threads_data.json"; // スレッドデータ保存先(サンプルなのでただのファイル)

// ---------------------------------------------------------
// データ読み書き(超簡易・排他制御なし・サンプル用)
// ---------------------------------------------------------
function load_threads($file) {
    if (!file_exists($file)) {
        return [];
    }
    $json = file_get_contents($file);
    $data = json_decode($json, true);
    if (!is_array($data)) {
        $data = [];
    }
    return $data;
}

function save_threads($file, $threads) {
    file_put_contents($file, json_encode($threads, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT));
}

$threads = load_threads($DATA_FILE);

// ---------------------------------------------------------
// 書き込み処理(新規スレッド作成 / レス書き込み)
// セキュリティ・バリデーションはサンプルなので最低限のみ
// ---------------------------------------------------------
if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    $from    = isset($_POST['FROM']) && $_POST['FROM'] !== '' ? $_POST['FROM'] : '名無しさん';
    $mail    = isset($_POST['mail']) ? $_POST['mail'] : '';
    $message = isset($_POST['MESSAGE']) ? $_POST['MESSAGE'] : '';
    $now     = date('Y/m/d(D) H:i:s');

    if (isset($_POST['submit']) && isset($_POST['subject'])) {
        // --- 新規スレッド作成 ---
        $subject = $_POST['subject'] !== '' ? $_POST['subject'] : '無題';

        $new_id = (string)time() . rand(100, 999);

        $threads[] = [
            'id'      => $new_id,
            'subject' => $subject,
            'posts'   => [
                [
                    'name'    => $from,
                    'mail'    => $mail,
                    'date'    => $now,
                    'message' => $message,
                ],
            ],
        ];

        save_threads($DATA_FILE, $threads);
        header("Location: ?tid=" . urlencode($new_id));
        exit;

    } elseif (isset($_POST['submit_res']) && isset($_POST['tid'])) {
        // --- 既存スレッドへのレス ---
        $tid = $_POST['tid'];

        foreach ($threads as &$t) {
            if ($t['id'] === $tid) {
                $t['posts'][] = [
                    'name'    => $from,
                    'mail'    => $mail,
                    'date'    => $now,
                    'message' => $message,
                ];
                break;
            }
        }
        unset($t);

        save_threads($DATA_FILE, $threads);
        header("Location: ?tid=" . urlencode($tid));
        exit;
    }
}

// ---------------------------------------------------------
// 表示モード判定(スレッド個別表示 or 板トップ)
// ---------------------------------------------------------
$view_tid = isset($_GET['tid']) ? $_GET['tid'] : null;

// 新しいスレッド・レスが上に来るように新着順(サンプルなので単純に配列を逆順)
$threads_sorted = array_reverse($threads);

// html出力用エスケープ(見た目が崩れないようにするための最低限の処理)
function h($s) {
    return htmlspecialchars((string)$s, ENT_QUOTES, 'UTF-8');
}

// 本文中の改行を<br>に変換
function nl($s) {
    return nl2br(h($s));
}
?>
<!DOCTYPE html>
<html lang="ja">
<head>
<meta charset="UTF-8">
<title><?php echo h($BOARD_TITLE); ?></title>
<style>
    body {
        background-image: url("assets/back.png");
        background-repeat: repeat;
        background-size: 65px 65px;
        margin: 0;
        padding: 10px 0;
        font-family: "MS Pゴシック", "MS PGothic", sans-serif;
        font-size: 14px;
    }
    a { color: #0000EE; }
    .thread-title {
        color: #FF0000;
        font-weight: bold;
    }
    .post-name {
        color: #008000;
        font-weight: bold;
    }
    .post-body, .post-meta {
        color: #000000;
    }
    .zenbu-link {
        text-align: right;
        margin-top: 4px;
    }
    .kubetsu {
        text-align: center;
        color: #666666;
        margin: 4px 0;
    }
</style>
</head>
<body>

<!-- ============================================================= -->
<!-- 冒頭の注意書き -->
<!-- ============================================================= -->
<table border="1" cellspacing="7" cellpadding="3" width="95%" bgcolor="#FFCCCC" align="center">
  <tbody>
    <tr>
      <td>
        <font color="#FF0000"><b>
        この掲示板はセキュリティ面とかも考えて使えたもんじゃないので、使うときは改変してください。
        </b></font>
      </td>
    </tr>
  </tbody>
</table>
<br>

<?php if ($view_tid === null): ?>

    <!-- ========================================================= -->
    <!-- 板トップページ -->
    <!-- ========================================================= -->

    <!-- 掲示板タイトル(緑の枠) -->
    <table border="1" cellspacing="7" cellpadding="3" width="95%" bgcolor="#CCFFCC" align="center">
      <tbody>
        <tr>
          <td>
            <font size="+3" color="#0000FF"><b><?php echo h($BOARD_TITLE); ?></b></font>
          </td>
        </tr>
      </tbody>
    </table>
    <br>

    <!-- 板の説明(緑の枠) -->
    <table border="1" cellspacing="7" cellpadding="3" width="95%" bgcolor="#CCFFCC" align="center">
      <tbody>
        <tr>
          <td>
            <?php echo nl($BOARD_DESC); ?>
          </td>
        </tr>
      </tbody>
    </table>
    <br>

<!-- スレッド一覧(緑の枠) -->
    <table border="1" cellspacing="7" cellpadding="3" width="95%" bgcolor="#CCFFCC" align="center">
      <tbody>
        <tr>
          <td>
            <b>スレッド一覧</b><br><br>
            <?php if (empty($threads_sorted)): ?>
              スレッドはまだありません。
            <?php else: ?>
              <?php foreach ($threads_sorted as $t): ?>
                <a href="?tid=<?php echo urlencode($t['id']); ?>">
                  <?php echo h($t['subject']); ?>
                </a>
                (<?php echo count($t['posts']); ?>)&nbsp;&nbsp;&nbsp;&nbsp;
              <?php endforeach; ?>
            <?php endif; ?>
          </td>
        </tr>
      </tbody>
    </table>
    <br>

    <!-- ===================================================== -->
    <!-- 各スレッドの概要(グレーの枠) -->
    <!-- 最初の1レス + 最新5レスを表示 -->
    <!-- ===================================================== -->
    <?php foreach ($threads_sorted as $t): ?>
        <?php
            $posts = $t['posts'];
            $total = count($posts);

            $first_post = $posts[0];
            // 最新5レス(先頭のレスと重複しないように)
            $last5 = array_slice($posts, max(1, $total - 5), null, true);
        ?>
        <table border="1" cellspacing="7" cellpadding="3" width="95%" bgcolor="#EFEFEF" align="center">
          <tbody>
            <tr>
              <td>
                <div class="thread-title">
                    <?php echo h($t['subject']); ?>
                    (全<?php echo $total; ?>件)
                </div>

                <!-- 最初の1レス -->
                <div>
                    <span class="post-name"><?php echo h($first_post['name']); ?></span>
                    <span class="post-meta">
                        <?php echo h($first_post['date']); ?> : 1
                    </span><br>
                    <span class="post-body"><?php echo nl($first_post['message']); ?></span>
                </div>

                <?php if ($total > 6): ?>
                    <div class="kubetsu">…(中略)…</div>
                <?php endif; ?>

                <!-- 最新5レス -->
                <?php if ($total > 1): ?>
                    <?php foreach ($last5 as $i => $p): ?>
                        <?php if ($i === 0) continue; // 先頭レスは既に表示済みなのでスキップ ?>
                        <div>
                            <span class="post-name"><?php echo h($p['name']); ?></span>
                            <span class="post-meta">
                                <?php echo h($p['date']); ?> : <?php echo $i + 1; ?>
                            </span><br>
                            <span class="post-body"><?php echo nl($p['message']); ?></span>
                        </div>
                    <?php endforeach; ?>
                <?php endif; ?>

                <div class="zenbu-link">
                    <a href="?tid=<?php echo urlencode($t['id']); ?>">全部見る</a>
                </div>
              </td>
            </tr>
          </tbody>
        </table>
        <br>
    <?php endforeach; ?>

<?php else: ?>

    <!-- ========================================================= -->
    <!-- スレッド個別ページ -->
    <!-- ========================================================= -->
    <?php
        $current = null;
        foreach ($threads as $t) {
            if ($t['id'] === $view_tid) {
                $current = $t;
                break;
            }
        }
    ?>

    <?php if ($current === null): ?>

        <table border="1" cellspacing="7" cellpadding="3" width="95%" bgcolor="#EFEFEF" align="center">
          <tbody>
            <tr><td>スレッドが見つかりませんでした。 <a href="?">板トップに戻る</a></td></tr>
          </tbody>
        </table>

    <?php else: ?>

<!-- スレッド全レス表示(グレーの枠) -->
        <table border="1" cellspacing="7" cellpadding="3" width="95%" bgcolor="#EFEFEF" align="center">
          <tbody>
            <tr>
              <td>
                <div class="thread-title">
                    <?php echo h($current['subject']); ?>
                </div>
                <br>
                <?php foreach ($current['posts'] as $i => $p): ?>
                    <div style="margin-bottom: 15px;">
                        <span class="post-meta"><?php echo $i + 1; ?> :</span>
                        <span class="post-name"><?php echo h($p['name']); ?></span>
                        <span class="post-meta">
                            <?php if (!empty($p['mail'])): ?>
                                [<?php echo h($p['mail']); ?>]
                            <?php endif; ?>
                            <?php echo h($p['date']); ?>
                        </span><br>
                        <span class="post-body"><?php echo nl($p['message']); ?></span>
                    </div>
                <?php endforeach; ?>
                <div class="zenbu-link">
                    <a href="?">板トップに戻る</a>
                </div>
              </td>
            </tr>
          </tbody>
        </table>
        <br>

        <!-- ================================================= -->
        <!-- 書き込みフォーム(緑の枠ではない / タイトル入力欄なし) -->
        <!-- ================================================= -->
        <table border="1" cellspacing="7" cellpadding="3" width="95%" align="center">
          <tbody>
            <tr>
              <td>
                <table border="0" cellpadding="4" width="99%" align="center">
                  <tbody>
                    <tr>
                      <td nowrap width="99%">
                        <font size="+1" color="#000000"><b><?php echo h($current['subject']); ?></b></font>
                      </td>
                      <td nowrap align="right" valign="top">&nbsp;</td>
                    </tr>
                    <tr>
                      <td colspan="2">
                        <form method="POST" action="?tid=<?php echo urlencode($current['id']); ?>"
                              onsubmit="this.MESSAGE.value = this.MESSAGE.value.replace(/[^\n\S]+$/gm, '').replace(/^ /gm, '&nbsp;');">
                          <table border="0" cellspacing="2" width="100%">
                            <tbody>
                              <tr>
                                <td nowrap align="right" width="16%">名 前：</td>
                                <td nowrap>
                                  <input name="FROM" size="19" value="">&nbsp; E-mail：
                                  <input name="mail" size="19" value="">
                                </td>
                              </tr>
                              <tr>
                                <td nowrap align="right" valign="top">内 容：</td>
                                <td>
                                  <textarea rows="5" cols="60" wrap="OFF" name="MESSAGE" style="margin:3px;"></textarea><br>
                                  <label>
                                    <input type="checkbox" onclick="this.form.MESSAGE.style.cssText = this.checked ? 'width: 90%; height: 15em; font-family: sans-serif; font-size: 1em;' : '';">AAモード
                                  </label>
                                  <br>
                                  <input type="submit" name="submit_res" value="書き込む">
                                </td>
                              </tr>
                            </tbody>
                          </table>
                          <input type="hidden" name="tid" value="<?php echo h($current['id']); ?>">
                        </form>
                      </td>
                    </tr>
                  </tbody>
                </table>
              </td>
            </tr>
          </tbody>
        </table>

    <?php endif; ?>

<?php endif; ?>

<?php if ($view_tid === null): ?>
<!-- ============================================================= -->
<!-- 新規スレッド作成フォーム(緑の枠) -->
<!-- ============================================================= -->
<table border="1" cellspacing="7" cellpadding="3" width="95%" bgcolor="#CCFFCC" align="center">
  <tbody>
    <tr>
      <td>
        <table border="0" cellpadding="4" width="99%" align="center">
          <tbody>
            <tr>
              <td nowrap width="99%">
                <font size="+1" color="#000000"><b><?php echo h($BOARD_TITLE); ?></b></font>
              </td>
              <td nowrap align="right" valign="top">&nbsp;</td>
            </tr>
            <tr>
              <td colspan="2">
                <ul style="line-height: 123%;"></ul>
                <form method="POST" action="?" onsubmit="this.MESSAGE.value = this.MESSAGE.value.replace(/[^\n\S]+$/gm, '').replace(/^ /gm, '&nbsp;');">
                  <table border="0" cellspacing="2" width="100%">
                    <tbody>
                      <tr>
                        <td nowrap align="right" width="16%">タイトル：</td>
                        <td>
                          <input name="subject" size="40" value="">&nbsp; &nbsp;
                          <input type="submit" name="submit" value="新規スレッド書込">
                        </td>
                      </tr>
                      <tr>
                        <td nowrap align="right" width="16%">名 前：</td>
                        <td nowrap>
                          <input name="FROM" size="19" value="">&nbsp; E-mail：
                          <input name="mail" size="19" value="">
                        </td>
                      </tr>
                      <tr>
                        <td nowrap align="right" valign="top">内 容：</td>
                        <td>
                          <textarea rows="5" cols="60" wrap="OFF" name="MESSAGE" style="margin:3px;"></textarea><br>
                          <label>
                            <input type="checkbox" onclick="this.form.MESSAGE.style.cssText = this.checked ? 'width: 90%; height: 15em; font-family: sans-serif; font-size: 1em;' : '';">AAモード
                          </label>
                        </td>
                      </tr>
                    </tbody>
                  </table>
                </form>
              </td>
            </tr>
          </tbody>
        </table>
      </td>
    </tr>
  </tbody>
</table>
<?php endif; ?>

</body>
</html>
