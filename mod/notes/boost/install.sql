CREATE TABLE notes (
  id int NOT NULL default '0',
  user_id int NOT NULL default '0',
  sender_id int NOT NULL default '0',
  title varchar(60) NOT NULL default '',
  content text,
  read_once smallint NOT NULL default '0',
  encrypted smallint NOT NULL default '0',
  date_sent int NOT NULL default '0',
  key_id int NOT NULL default '0',
  PRIMARY KEY  (id)
);


CREATE INDEX notes_idx on notes(user_id, key_id);
