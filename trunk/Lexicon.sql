CREATE TABLE IF NOT EXISTS `Lexicon_cache` (
  `id` int(11) NOT NULL,
  `term` varchar(256) NOT NULL,
  `def` varchar(1024) NOT NULL,
  `url` varchar(64) NOT NULL,
  `dirty` tinyint(1) NOT NULL default '1',
  PRIMARY KEY  (`id`)
) ENGINE=MyISAM DEFAULT CHARSET=latin1;
delimiter //
DROP TRIGGER IF EXISTS `Lexicon_cache_invalid_updatetemplate`//
DROP TRIGGER IF EXISTS `Lexicon_cache_invalid_newtemplate`//
DROP TRIGGER IF EXISTS `Lexicon_cache_invalid_updatetemplate`//
CREATE TRIGGER `Lexicon_cache_invalid_updatetemplate` BEFORE INSERT ON `revision`
 FOR EACH ROW BEGIN
declare foundid integer;
select count(*) into foundid  from templatelinks where tl_from=NEW.rev_page;
IF foundid > 0  THEN
 INSERT INTO Lexicon_cache (ID) VALUES (NEW.rev_page)
ON DUPLICATE KEY UPDATE  Lexicon_cache.dirty = '1' ;
END IF;
END
//
DROP TRIGGER IF EXISTS `Lexicon_cache_insert_newtemplate`//
CREATE TRIGGER `Lexicon_cache_insert_newtemplate` BEFORE INSERT ON `templatelinks`
 FOR EACH ROW insert into `Lexicon_cache` (id) VALUE (NEW.tl_from)
//
DROP TRIGGER IF EXISTS `Lexicon_cache_delete_template`//
CREATE TRIGGER `Lexicon_cache_delete_template` BEFORE DELETE ON `templatelinks`
 FOR EACH ROW DELETE from  `Lexicon_cache` where id=OLD.tl_from
//

delimiter ; 
