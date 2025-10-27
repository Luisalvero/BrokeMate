<div class="grid cols-2">
  <div class="card">
    <h2>Split expenses, stay friends.</h2>
    <p class="muted">Create a group for your room, trip, or club and keep tabs clear. Lightweight, private, no ads.</p>
    <div class="row">
      <a class="btn" href="/groups/create">Create Group</a>
      <a class="btn secondary" href="/groups/join">Join with Code</a>
    </div>
  </div>
  <div class="card">
    <h3>Quick Guest</h3>
    <p>Create a temporary account. You can set a password later to keep it.</p>
    <form method="post" action="/guest">
      <?= App\Lib\CSRF::field() ?>
      <div class="field"><label>Name</label><input class="input" type="text" name="name" placeholder="e.g., TripBuddy"/></div>
      <button class="btn">Continue as Guest</button>
    </form>
  </div>
</div>
