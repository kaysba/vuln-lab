-- 100% fake data.
SET NAMES utf8mb4;

INSERT INTO users (id, username, email, password, role, bio) VALUES
(1, 'admin', 'admin@vuln-lab.local', '0d107d09f5bbe40cade3de5c71e9e9b7', 'admin', 'Forum administrator. I moderate, I don''t bite.'),
(2, 'alice', 'alice@vuln-lab.local', '7c6a180b36896a0a8c02787eeafb0e4c', 'user', 'Web developer, into PHP and coffee.'),
(3, 'bob', 'bob@vuln-lab.local', 'd8578edf8458ce06fbc5bb76a58c5ca4', 'user', 'Networking student, I tinker with homelabs.'),
(4, 'carol', 'carol@vuln-lab.local', 'e10adc3949ba59abbe56e057f20f883e', 'user', 'I test anything that has a button.'),
(5, 'dave', 'dave@vuln-lab.local', 'f25a2fc72690b780b2a14e140ef6a9e0', 'user', 'Into role-playing games and Linux.'),
(6, 'eve', 'eve@vuln-lab.local', '0571749e2ac330a7455809c6b0e7af90', 'user', 'Curious by nature. Too curious, sometimes.');

INSERT INTO posts (id, user_id, title, body) VALUES
(1, 1, 'Welcome to the forum', 'Welcome, everyone. Please be polite and read the rules before posting.'),
(2, 2, 'Which editor do you use?', 'I can''t decide between VS Code and a terminal editor. Any feedback?'),
(3, 3, 'My first homelab', 'I set up a small server with an old PC. I''ll share my notes soon.'),
(4, 4, 'Bug on the profile page', 'My avatar doesn''t show up. Anyone else having this problem?'),
(5, 5, 'Role-playing night on Saturday', 'We''re running a game on Saturday night, two seats left.'),
(6, 6, 'Tips for learning networking', 'I''m a beginner. Which courses or books would you recommend?'),
(7, 2, 'PHP 8: the useful new features', 'Named arguments and match really improve code readability.'),
(8, 3, 'Backups: how do you do it?', 'Rsync + an external drive, is that enough for personal use?'),
(9, 1, 'Scheduled maintenance on Sunday', 'The forum will be down for one hour on Sunday morning for maintenance.'),
(10, 5, 'TV series recommendations', 'I''m looking for a good crime series, any ideas?');

INSERT INTO comments (post_id, user_id, body) VALUES
(1, 2, 'Thanks for the welcome, glad to be here!'),
(2, 3, 'VS Code for me, the extensions are handy.'),
(2, 5, 'Vim, but you have to accept the learning curve.'),
(3, 6, 'Great project, which distro are you using?'),
(4, 1, 'Thanks for the report, I''m looking into it.'),
(5, 4, 'Count me in for Saturday!'),
(6, 3, 'Start with the OSI model, then practice with Wireshark.'),
(8, 2, 'Remember to test restoring too, that''s the key.'),
(9, 6, 'Thanks for the advance notice.'),
(10, 4, 'Try a Scandinavian series, they''re excellent.');

INSERT INTO messages (sender_id, recipient_id, subject, body) VALUES
(2, 3, 'Question about your homelab', 'Hi Bob, could you explain how you set up your network?'),
(3, 2, 'Re: Question about your homelab', 'Sure, I''ll write you a short summary this weekend.'),
(1, 4, 'Your report', 'Hi Carol, I got your report about the avatar, a fix is on the way.'),
(4, 1, 'Re: Your report', 'Thanks admin, keep me posted.'),
(5, 6, 'Saturday game', 'Eve, are you coming on Saturday? We start at 8pm.'),
(6, 5, 'Re: Saturday game', 'Yes, happy to, I''ll bring snacks.'),
(2, 1, 'Moderation request', 'Hello, a post on the forum looks off-topic to me, could you take a look?'),
(1, 2, 'Re: Moderation request', 'Thanks Alice, noted, I''ll check it.'),
(1, 3, 'Sunday maintenance', 'Bob, could you help me test the forum after the maintenance?'),
(3, 5, 'Borrowed book', 'Dave, I''ll give you back your networking book on Tuesday.'),
(6, 1, 'Email address change', 'Hello, I''d like to change my email address, how do I do that?');
